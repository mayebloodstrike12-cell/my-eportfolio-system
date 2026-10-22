<?php
// Site configuration constants
define('SITE_NAME', "ANTHONY MAYER P. GUANZON");
define('SITE_QUOTE', '"Success is liking yourself, liking what you do, and liking how you do it." Maya Angelou');
define('AUTHOR_NAME', 'ANTHONY MAYER P. GUANZON');

// Include Database connection
require_once __DIR__ . '/db.php';

/**
 * Get all posts from data store (Database with JSON backup fallback)
 */
function get_all_posts() {
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM posts ORDER BY id ASC");
            $posts = $stmt->fetchAll();
            if (!empty($posts)) {
                return $posts;
            }
        } catch (PDOException $e) {
            error_log("Error fetching posts from DB: " . $e->getMessage());
        }
    }

    // Fallback to JSON file
    $file = __DIR__ . '/../data/posts.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        return json_decode($json, true) ?? [];
    }
    return [];
}

/**
 * Get post by slug or ID
 */
function get_post($id_or_slug) {
    $posts = get_all_posts();
    foreach ($posts as $post) {
        if ($post['id'] == $id_or_slug || (isset($post['slug']) && $post['slug'] == $id_or_slug)) {
            return $post;
        }
    }
    return null;
}

/**
 * Get comments for a specific post
 */
function get_comments($post_id) {
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = :post_id ORDER BY id ASC");
            $stmt->execute([':post_id' => intval($post_id)]);
            $comments = $stmt->fetchAll();
            if (!empty($comments)) {
                return $comments;
            }
        } catch (PDOException $e) {
            error_log("Error fetching comments from DB: " . $e->getMessage());
        }
    }

    // Fallback to JSON file
    $file = __DIR__ . '/../data/comments.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $comments = json_decode($json, true) ?? [];
        return array_values(array_filter($comments, function($c) use ($post_id) {
            return $c['post_id'] == $post_id;
        }));
    }
    return [];
}

/**
 * Add a new comment
 */
function add_comment($post_id, $author, $comment_text) {
    $clean_author = htmlspecialchars(trim($author));
    $clean_comment = htmlspecialchars(trim($comment_text));
    $date_str = date('F j, Y \a\t g:i A');
    $p_id = intval($post_id);

    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, author, date, comment) VALUES (:post_id, :author, :date, :comment)");
            $stmt->execute([
                ':post_id' => $p_id,
                ':author' => $clean_author,
                ':date' => $date_str,
                ':comment' => $clean_comment
            ]);
        } catch (PDOException $e) {
            error_log("Error inserting comment to DB: " . $e->getMessage());
        }
    }

    // Sync to JSON file backup
    $file = __DIR__ . '/../data/comments.json';
    $comments = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $comments = json_decode($json, true) ?? [];
    }

    $comments[] = [
        'id' => count($comments) + 1,
        'post_id' => $p_id,
        'author' => $clean_author,
        'date' => $date_str,
        'comment' => $clean_comment
    ];
    file_put_contents($file, json_encode($comments, JSON_PRETTY_PRINT));
    return true;
}

/**
 * Active link navigation class helper
 */
function is_active($page_name) {
    $current = basename($_SERVER['SCRIPT_NAME']);
    if ($current === $page_name) {
        return 'active';
    }
    return '';
}

/**
 * Get all activities from MySQL Database (or fallback JSON storage), sorted with pinned items at top
 */
function get_all_activities() {
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, title, content, date, pinned FROM activities ORDER BY pinned DESC, created_at DESC, id DESC");
            $rows = $stmt->fetchAll();
            $activities = [];
            foreach ($rows as $row) {
                $activities[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'date' => $row['date'],
                    'pinned' => (bool)$row['pinned']
                ];
            }

            // Sync database records back to JSON backup file
            $file = __DIR__ . '/../data/activities.json';
            file_put_contents($file, json_encode($activities, JSON_PRETTY_PRINT));

            return $activities;
        } catch (PDOException $e) {
            error_log("Error fetching activities from DB: " . $e->getMessage());
        }
    }

    // Fallback to JSON file if DB unavailable
    $file = __DIR__ . '/../data/activities.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $activities = json_decode($json, true) ?? [];
        
        // Sort so pinned items appear at the very top
        usort($activities, function($a, $b) {
            $a_pinned = !empty($a['pinned']);
            $b_pinned = !empty($b['pinned']);
            if ($a_pinned && !$b_pinned) return -1;
            if (!$a_pinned && $b_pinned) return 1;
            return 0;
        });
        return $activities;
    }
    return [];
}

/**
 * Get image attachments for one activity.
 */
function get_activity_images($activity_id) {
    $pdo = get_db_connection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("SELECT id, image_path FROM activity_images WHERE activity_id = :activity_id ORDER BY id ASC");
        $stmt->execute([':activity_id' => $activity_id]);
        $images = $stmt->fetchAll();
        $safe_images = [];
        $upload_root = realpath(__DIR__ . '/../uploads/activities');
        foreach ($images as $image) {
            $prefix = 'uploads/activities/';
            if (strpos($image['image_path'], $prefix) !== 0) {
                continue;
            }
            $filename = substr($image['image_path'], strlen($prefix));
            if ($filename === '' || basename($filename) !== $filename || !preg_match('/\.(jpg|png|webp)$/i', $filename)) {
                continue;
            }
            $full_path = realpath(__DIR__ . '/../' . $prefix . $filename);
            if ($upload_root && $full_path && strpos($full_path, $upload_root . DIRECTORY_SEPARATOR) === 0 && is_file($full_path)) {
                $safe_images[] = $image;
            }
        }
        return $safe_images;
    } catch (PDOException $e) {
        error_log("Error fetching activity images: " . $e->getMessage());
        return [];
    }
}

/**
 * Store an image attachment and associate it with an activity.
 */
function add_activity_image($activity_id, $image_path) {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new RuntimeException("Image attachments require an available database connection.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_images (activity_id, image_path) VALUES (:activity_id, :image_path)");
        $stmt->execute([
            ':activity_id' => $activity_id,
            ':image_path' => $image_path
        ]);
    } catch (PDOException $e) {
        error_log("Error saving activity image: " . $e->getMessage());
        throw new RuntimeException("The image could not be saved to the database.");
    }
}

/**
 * Remove an image only when it belongs to the specified activity.
 */
function delete_activity_image($activity_id, $image_id) {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new RuntimeException("Image attachments require an available database connection.");
    }

    try {
        $stmt = $pdo->prepare("SELECT image_path FROM activity_images WHERE id = :id AND activity_id = :activity_id");
        $stmt->execute([
            ':id' => $image_id,
            ':activity_id' => $activity_id
        ]);
        $image = $stmt->fetch();
        if (!$image) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM activity_images WHERE id = :id AND activity_id = :activity_id");
        $stmt->execute([
            ':id' => $image_id,
            ':activity_id' => $activity_id
        ]);
        remove_activity_image_file($image['image_path']);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing activity image: " . $e->getMessage());
        throw new RuntimeException("The selected image could not be removed.");
    }
}

/**
 * Delete a stored activity image only from the dedicated upload directory.
 */
function remove_activity_image_file($image_path) {
    $prefix = 'uploads/activities/';
    if (strpos($image_path, $prefix) !== 0) {
        return;
    }

    $filename = substr($image_path, strlen($prefix));
    if ($filename === '' || basename($filename) !== $filename) {
        return;
    }

    $file = __DIR__ . '/../' . $prefix . $filename;
    if (is_file($file)) {
        unlink($file);
    }
}

/**
 * Add a new activity to Database
 */
function add_activity($title, $content, $date = '', $pinned = false) {
    if (empty($date)) {
        $date = date('m/d');
    }

    $new_activity = [
        'id' => 'act_' . time() . '_' . rand(100, 999),
        'date' => htmlspecialchars(trim($date)),
        'title' => htmlspecialchars(trim($title)),
        'content' => htmlspecialchars(trim($content)),
        'pinned' => (bool)$pinned
    ];

    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activities (id, title, content, date, pinned) VALUES (:id, :title, :content, :date, :pinned)");
            $stmt->execute([
                ':id' => $new_activity['id'],
                ':title' => $new_activity['title'],
                ':content' => $new_activity['content'],
                ':date' => $new_activity['date'],
                ':pinned' => $new_activity['pinned'] ? 1 : 0
            ]);
        } catch (PDOException $e) {
            error_log("Error adding activity to DB: " . $e->getMessage());
            throw new RuntimeException("The activity could not be saved to the database.");
        }
    }

    // Sync to JSON file backup
    $file = __DIR__ . '/../data/activities.json';
    $activities = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $activities = json_decode($json, true) ?? [];
    }
    array_unshift($activities, $new_activity);
    file_put_contents($file, json_encode($activities, JSON_PRETTY_PRINT));

    return $new_activity;
}

/**
 * Update an existing activity in Database
 */
function update_activity($id, $title, $content, $date = '', $pinned = false) {
    $clean_title = htmlspecialchars(trim($title));
    $clean_content = htmlspecialchars(trim($content));
    $clean_date = !empty($date) ? htmlspecialchars(trim($date)) : date('m/d');
    $is_pinned = (bool)$pinned;

    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE activities SET title = :title, content = :content, date = :date, pinned = :pinned WHERE id = :id");
            $stmt->execute([
                ':title' => $clean_title,
                ':content' => $clean_content,
                ':date' => $clean_date,
                ':pinned' => $is_pinned ? 1 : 0,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating activity in DB: " . $e->getMessage());
            throw new RuntimeException("The activity could not be updated in the database.");
        }
    }

    // Sync to JSON file backup
    $file = __DIR__ . '/../data/activities.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $activities = json_decode($json, true) ?? [];
        foreach ($activities as &$act) {
            if ($act['id'] === $id) {
                $act['title'] = $clean_title;
                $act['content'] = $clean_content;
                $act['date'] = $clean_date;
                $act['pinned'] = $is_pinned;
                break;
            }
        }
        file_put_contents($file, json_encode($activities, JSON_PRETTY_PRINT));
    }

    return true;
}

/**
 * Toggle pin status of an activity in Database
 */
function toggle_pin_activity($id) {
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE activities SET pinned = CASE WHEN pinned = 1 THEN 0 ELSE 1 END WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error toggling pin in DB: " . $e->getMessage());
            throw new RuntimeException("The activity pin status could not be updated.");
        }
    }

    // Sync to JSON file backup
    $file = __DIR__ . '/../data/activities.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $activities = json_decode($json, true) ?? [];
        foreach ($activities as &$act) {
            if ($act['id'] === $id) {
                $act['pinned'] = empty($act['pinned']);
                break;
            }
        }
        file_put_contents($file, json_encode($activities, JSON_PRETTY_PRINT));
    }

    return true;
}

/**
 * Delete an activity by ID from Database
 */
function delete_activity($id) {
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $image_stmt = $pdo->prepare("SELECT image_path FROM activity_images WHERE activity_id = :id");
            $image_stmt->execute([':id' => $id]);
            $image_paths = $image_stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = :id");
            $stmt->execute([':id' => $id]);
            foreach ($image_paths as $image_path) {
                remove_activity_image_file($image_path);
            }
        } catch (PDOException $e) {
            error_log("Error deleting activity from DB: " . $e->getMessage());
            throw new RuntimeException("The activity could not be deleted from the database.");
        }
    }

    // Sync to JSON file backup
    $file = __DIR__ . '/../data/activities.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $activities = json_decode($json, true) ?? [];
        $filtered = array_values(array_filter($activities, function($act) use ($id) {
            return $act['id'] !== $id;
        }));
        file_put_contents($file, json_encode($filtered, JSON_PRETTY_PRINT));
    }

    return true;
}
