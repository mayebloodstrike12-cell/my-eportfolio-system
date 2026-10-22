<?php
$page_title = "Activities & Document Notes - ANTHONY MAYER P. GUANZON";
require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['activities_csrf'])) {
    $_SESSION['activities_csrf'] = bin2hex(random_bytes(32));
}

function validate_activity_image_uploads($files) {
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $uploads = [];
    $total_size = 0;
    $max_file_size = 5 * 1024 * 1024;
    $max_files = 6;
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $file_info = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files['name'] as $index => $name) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("An image upload failed. Please select the images again.");
        }
        if (count($uploads) >= $max_files) {
            throw new RuntimeException("You can attach up to 6 images per activity.");
        }

        $tmp_name = $files['tmp_name'][$index] ?? '';
        $size = (int)($files['size'][$index] ?? 0);
        if ($size < 1 || $size > $max_file_size || !is_uploaded_file($tmp_name)) {
            throw new RuntimeException("Each image must be smaller than 5 MB.");
        }

        $mime_type = $file_info->file($tmp_name);
        if (!isset($allowed_types[$mime_type])) {
            throw new RuntimeException("Only JPG, PNG, and WEBP images are allowed.");
        }

        $image_info = getimagesize($tmp_name);
        if ($image_info === false || $image_info['mime'] !== $mime_type) {
            throw new RuntimeException("One of the selected files is not a valid image.");
        }

        $total_size += $size;
        if ($total_size > 20 * 1024 * 1024) {
            throw new RuntimeException("The combined image upload size cannot exceed 20 MB.");
        }

        $uploads[] = [
            'tmp_name' => $tmp_name,
            'extension' => $allowed_types[$mime_type]
        ];
    }

    return $uploads;
}

function store_activity_image_uploads($activity_id, $uploads) {
    if (empty($uploads)) {
        return;
    }

    $upload_directory = __DIR__ . '/uploads/activities';
    if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
        throw new RuntimeException("The image upload directory could not be created.");
    }
    if (!is_writable($upload_directory)) {
        throw new RuntimeException("The image upload directory is not writable by PHP.");
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        throw new RuntimeException("Image attachments require an available database connection.");
    }

    $stored_files = [];
    try {
        $pdo->beginTransaction();
        foreach ($uploads as $upload) {
            $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
            $destination = $upload_directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                throw new RuntimeException("An uploaded image could not be moved into storage.");
            }

            $relative_path = 'uploads/activities/' . $filename;
            $stored_files[] = $destination;
            add_activity_image($activity_id, $relative_path);
        }
        $pdo->commit();
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($stored_files as $stored_file) {
            if (is_file($stored_file)) {
                unlink($stored_file);
            }
        }
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($stored_files as $stored_file) {
            if (is_file($stored_file)) {
                unlink($stored_file);
            }
        }
        if ($e instanceof RuntimeException) {
            throw $e;
        }
        error_log("Activity image storage failed: " . $e->getMessage());
        throw new RuntimeException("The image files could not be saved.");
    }
}

function set_activity_flash($type, $message) {
    $_SESSION['activities_flash'] = ['type' => $type, 'message' => $message];
}

function normalize_activity_content($content) {
    $content = str_replace(["\r\n", "\r", "\u{2028}", "\u{2029}"], "\n", (string)$content);
    return trim($content);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['_csrf'] ?? '';
    if (!is_string($posted_token) || !hash_equals($_SESSION['activities_csrf'], $posted_token)) {
        set_activity_flash('error', 'Your request could not be verified. Please try again.');
        header("Location: activities.php");
        exit;
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = trim($_POST['id'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $content = normalize_activity_content($_POST['content'] ?? '');
            $date = trim($_POST['date'] ?? date('m/d'));
            $pinned = isset($_POST['pinned']) && $_POST['pinned'] === '1';
            $uploads = validate_activity_image_uploads($_FILES['images'] ?? []);
            $requested_removals = $_POST['remove_images'] ?? [];
            if (!is_array($requested_removals)) {
                $requested_removals = [];
            }

            if ($title === '') {
                throw new RuntimeException("Please enter an activity title.");
            }

            $existing_images = $id !== '' ? get_activity_images($id) : [];
            $removal_ids = array_unique(array_filter($requested_removals, function($image_id) {
                return is_scalar($image_id) && ctype_digit((string)$image_id);
            }));
            $removal_count = 0;
            foreach ($existing_images as $image) {
                if (in_array((string)$image['id'], $removal_ids, true)) {
                    $removal_count++;
                }
            }
            if (count($existing_images) - $removal_count + count($uploads) > 6) {
                throw new RuntimeException("An activity can have up to 6 images. Remove an existing image before adding another.");
            }

            if ($id !== '') {
                update_activity($id, $title, $content, $date, $pinned);
                $activity_id = $id;
            } else {
                $new_activity = add_activity($title, $content, $date, $pinned);
                $activity_id = $new_activity['id'];
            }

            foreach ($requested_removals as $image_id) {
                if (ctype_digit((string)$image_id)) {
                    delete_activity_image($activity_id, (int)$image_id);
                }
            }
            try {
                store_activity_image_uploads($activity_id, $uploads);
                set_activity_flash('success', 'Activity saved successfully.');
            } catch (RuntimeException $e) {
                set_activity_flash('error', 'Activity saved, but image upload failed: ' . $e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = trim($_POST['id'] ?? '');
            if ($id === '') {
                throw new RuntimeException("The activity could not be identified.");
            }
            delete_activity($id);
            set_activity_flash('success', 'Activity deleted.');
        } elseif ($action === 'toggle_pin') {
            $id = trim($_POST['id'] ?? '');
            if ($id === '') {
                throw new RuntimeException("The activity could not be identified.");
            }
            toggle_pin_activity($id);
            set_activity_flash('success', 'Pin status updated.');
        } else {
            throw new RuntimeException("The requested activity action is not supported.");
        }
    } catch (RuntimeException $e) {
        set_activity_flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log("Activity action failed: " . $e->getMessage());
        set_activity_flash('error', 'The activity could not be saved. Please try again.');
    }

    header("Location: activities.php");
    exit;
}

$activities = get_all_activities();
$pinned_activities = array_filter($activities, function($activity) { return !empty($activity['pinned']); });
$other_activities = array_filter($activities, function($activity) { return empty($activity['pinned']); });
$flash = $_SESSION['activities_flash'] ?? null;
unset($_SESSION['activities_flash']);
$csrf_token = $_SESSION['activities_csrf'];
require_once __DIR__ . '/includes/header.php';

$escape = function($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$render_activity_card = function($activity) use ($escape, $csrf_token) {
    $id = $escape($activity['id']);
    $title = html_entity_decode($activity['title'], ENT_QUOTES, 'UTF-8');
    $content = html_entity_decode($activity['content'], ENT_QUOTES, 'UTF-8');
    $date = html_entity_decode($activity['date'], ENT_QUOTES, 'UTF-8');
    $pinned = !empty($activity['pinned']);
    $images = get_activity_images($activity['id']);
    $preview = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
    if (strlen($preview) > 150) {
        $preview = substr($preview, 0, 147) . '...';
    }
    $images_json = json_encode($images, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>
    <article class="note-card<?php echo $pinned ? ' pinned-card' : ''; ?>"
             tabindex="0" role="button" aria-label="Open activity: <?php echo $escape($title); ?>"
             data-id="<?php echo $id; ?>"
             data-title="<?php echo $escape($title); ?>"
             data-content="<?php echo $escape($content); ?>"
             data-date="<?php echo $escape($date); ?>"
             data-pinned="<?php echo $pinned ? '1' : '0'; ?>"
             data-images="<?php echo $escape($images_json ?: '[]'); ?>">
        <div class="note-card-header">
            <span class="note-date"><?php if ($pinned): ?><span class="pin-icon" aria-label="Pinned">📌</span><?php endif; ?><?php echo $escape($date); ?></span>
            <div class="card-quick-actions">
                <form action="activities.php" method="POST" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?php echo $escape($csrf_token); ?>">
                    <input type="hidden" name="action" value="toggle_pin">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn-quick-pin<?php echo $pinned ? ' active' : ''; ?>" title="<?php echo $pinned ? 'Unpin note' : 'Pin to top'; ?>" aria-label="<?php echo $pinned ? 'Unpin note' : 'Pin note'; ?>"><?php echo $pinned ? '📌' : '📍'; ?></button>
                </form>
                <button type="button" class="btn-card-edit" aria-label="Edit activity" title="Edit activity">Edit</button>
                <form action="activities.php" method="POST" class="inline-form" data-confirm="Delete this document note?">
                    <input type="hidden" name="_csrf" value="<?php echo $escape($csrf_token); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn-delete-note" title="Delete Note" aria-label="Delete note">&times;</button>
                </form>
            </div>
        </div>
        <h3 class="note-title"><?php echo $escape($title); ?></h3>
        <p class="note-content"><?php echo $escape($preview); ?></p>
        <?php if (!empty($images)): ?><span class="note-attachment-count"><?php echo count($images); ?> image<?php echo count($images) === 1 ? '' : 's'; ?></span><?php endif; ?>
    </article>
    <?php
};
?>

<main class="site-content">
    <div class="notepad-container">
        <div class="notepad-header">
            <div class="notepad-title-row">
                <h1 class="notepad-title">Activities &amp; Document Notes</h1>
                <div class="notepad-actions">
                    <button class="btn-icon" id="openAddDocBtn" type="button">📝 New Document</button>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="activity-flash <?php echo $escape($flash['type']); ?>" role="status"><?php echo $escape($flash['message']); ?></div>
            <?php endif; ?>
            <div class="search-box-wrap">
                <span class="search-icon" aria-hidden="true">🔍</span>
                <input type="search" id="searchInput" class="search-input" placeholder="Search document notes and activities..." aria-label="Search activities">
            </div>
        </div>

        <div class="section-label pinned-section-label" <?php echo empty($pinned_activities) ? 'hidden' : ''; ?>>
            <span>📌 PINNED ACTIVITIES</span>
        </div>
        <div class="notes-grid pinned-grid" id="pinnedGrid" <?php echo empty($pinned_activities) ? 'hidden' : ''; ?>>
            <?php foreach ($pinned_activities as $activity) { $render_activity_card($activity); } ?>
        </div>

        <div class="section-label all-section-label" style="margin-top: 30px;">
            <span>ALL ACTIVITIES</span>
        </div>
        <div class="notes-grid" id="otherGrid">
            <?php foreach ($other_activities as $activity) { $render_activity_card($activity); } ?>
            <?php if (empty($activities)): ?>
                <div class="empty-notes" id="emptyNotes">No activities saved yet. Select <strong>New Document</strong> to create your first activity note.</div>
            <?php endif; ?>
        </div>
        <div class="empty-notes search-empty" id="searchEmpty" hidden>No activities match your search. Try another title or keyword.</div>
    </div>
</main>

<button class="fab-btn" id="fabAddBtn" type="button" title="Create New Document Activity" aria-label="Create new activity">+</button>

<div class="modal-overlay" id="detailModal" aria-hidden="true">
    <section class="detail-dialog" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <header class="detail-header">
            <div><span class="doc-badge">ACTIVITY DETAILS</span><h2 id="detailTitle"></h2><p id="detailDate" class="detail-date"></p></div>
            <button type="button" class="doc-close-btn" id="closeDetailBtn" aria-label="Close details">&times;</button>
        </header>
        <div class="detail-body">
            <div id="detailContent" class="detail-content"></div>
            <div id="detailImages" class="detail-images"></div>
        </div>
        <footer class="detail-footer">
            <button type="button" class="btn-secondary" id="detailEditBtn">Edit activity</button>
            <button type="button" class="btn-primary" id="detailCloseBtn">Close</button>
        </footer>
    </section>
</div>

<div class="modal-overlay" id="docModal" aria-hidden="true">
    <div class="doc-paper-card" role="dialog" aria-modal="true" aria-label="Activity editor">
        <form action="activities.php" method="POST" id="docForm" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?php echo $escape($csrf_token); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="docId" value="">
            <input type="hidden" name="pinned" id="docPinnedInput" value="0">
            <div class="doc-toolbar">
                <div class="doc-tag"><span class="doc-badge">DOCUMENT EDITOR</span></div>
                <div class="doc-actions">
                    <button type="button" class="btn-doc-action" id="docPinBtn"><span id="pinBtnText">📌 Pin to Top</span></button>
                    <button type="button" class="btn-doc-action btn-doc-delete" id="docDeleteBtn" hidden>🗑️ Delete</button>
                    <button type="submit" class="btn-doc-action btn-doc-save">💾 Save Document</button>
                    <button type="button" class="doc-close-btn" id="closeDocModalBtn" aria-label="Close editor">&times;</button>
                </div>
            </div>
            <div class="doc-sheet">
                <div class="doc-meta-row">
                    <label for="docDate">DATE:</label>
                    <input type="text" id="docDate" name="date" class="doc-date-input" value="<?php echo $escape(date('m/d')); ?>" required>
                </div>
                <div class="doc-title-row"><input type="text" id="docTitle" name="title" class="doc-title-input" placeholder="Document Title / Activity Name..." required></div>
                <div class="doc-content-row">
                    <textarea id="docContent" name="content" class="doc-content-textarea" placeholder="Start typing your activity notes, documentation, tasks, or course details here..." rows="12"></textarea>
                    <p class="content-format-hint">Paste or type your text here. Paragraph breaks and line breaks are kept when you save.</p>
                </div>
                <section class="image-editor">
                    <label for="activityImages">Attach images <span>(JPG, PNG, WEBP; up to 6 images, 5 MB each)</span></label>
                    <input type="file" id="activityImages" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                    <div id="imagePreviews" class="image-previews" aria-live="polite"></div>
                    <div id="existingImages" class="existing-images"></div>
                </section>
            </div>
        </form>
        <form action="activities.php" method="POST" id="docDeleteForm" hidden>
            <input type="hidden" name="_csrf" value="<?php echo $escape($csrf_token); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="docDeleteId" value="">
        </form>
    </div>
</div>

<script src="assets/js/activities.js?v=<?php echo filemtime(__DIR__ . '/assets/js/activities.js'); ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
