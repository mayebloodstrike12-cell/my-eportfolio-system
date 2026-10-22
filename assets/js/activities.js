document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const cards = Array.from(document.querySelectorAll('.note-card'));
    const pinnedGrid = document.getElementById('pinnedGrid');
    const pinnedLabel = document.querySelector('.pinned-section-label');
    const searchEmpty = document.getElementById('searchEmpty');
    const detailModal = document.getElementById('detailModal');
    const editorModal = document.getElementById('docModal');
    const form = document.getElementById('docForm');
    const imagesInput = document.getElementById('activityImages');
    const previews = document.getElementById('imagePreviews');
    const existingImages = document.getElementById('existingImages');
    let selectedCard = null;
    let pinnedState = false;

    function parseImages(card) {
        try {
            return JSON.parse(card.dataset.images || '[]');
        } catch (error) {
            console.error('Could not read activity images.', error);
            return [];
        }
    }

    function setModalState(modal, open) {
        modal.classList.toggle('active', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('modal-open', open || detailModal.classList.contains('active') || editorModal.classList.contains('active'));
    }

    function clearImages() {
        previews.replaceChildren();
        existingImages.replaceChildren();
        imagesInput.value = '';
    }

    function openEditor(card) {
        clearImages();
        selectedCard = card || null;
        document.getElementById('docId').value = card ? card.dataset.id : '';
        document.getElementById('docDate').value = card ? card.dataset.date : new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit' });
        document.getElementById('docTitle').value = card ? card.dataset.title : '';
        document.getElementById('docContent').value = card ? card.dataset.content : '';
        document.getElementById('docDeleteId').value = card ? card.dataset.id : '';
        document.getElementById('docDeleteBtn').hidden = !card;
        pinnedState = card ? card.dataset.pinned === '1' : false;
        updatePinButton();

        if (card) {
            parseImages(card).forEach(function (image) {
                const label = document.createElement('label');
                label.className = 'existing-image';
                const img = document.createElement('img');
                img.src = image.image_path;
                img.alt = 'Attached activity image';
                const remove = document.createElement('input');
                remove.type = 'checkbox';
                remove.name = 'remove_images[]';
                remove.value = image.id;
                const text = document.createElement('span');
                text.textContent = 'Remove';
                label.append(img, remove, text);
                existingImages.append(label);
            });
        }
        setModalState(detailModal, false);
        setModalState(editorModal, true);
        window.setTimeout(function () { document.getElementById('docTitle').focus(); }, 160);
    }

    function updatePinButton() {
        document.getElementById('docPinnedInput').value = pinnedState ? '1' : '0';
        document.getElementById('docPinBtn').classList.toggle('pinned-active', pinnedState);
        document.getElementById('pinBtnText').textContent = pinnedState ? '📌 Pinned (Top)' : '📌 Pin to Top';
    }

    function renderActivityContent(content, container) {
        container.replaceChildren();
        const normalized = content.replace(/\r\n?/g, '\n').replace(/[\u2028\u2029]/g, '\n');
        const paragraphs = normalized.split(/\n[ \t]*\n+/);

        paragraphs.forEach(function (paragraphText) {
            if (paragraphText === '') return;
            const paragraph = document.createElement('p');
            paragraphText.split('\n').forEach(function (line, index) {
                if (index > 0) paragraph.append(document.createElement('br'));
                paragraph.append(document.createTextNode(line));
            });
            container.append(paragraph);
        });
    }

    function openDetails(card) {
        selectedCard = card;
        document.getElementById('detailTitle').textContent = card.dataset.title;
        document.getElementById('detailDate').textContent = card.dataset.date;
        renderActivityContent(card.dataset.content, document.getElementById('detailContent'));
        const imageContainer = document.getElementById('detailImages');
        imageContainer.replaceChildren();
        parseImages(card).forEach(function (image) {
            const img = document.createElement('img');
            img.src = image.image_path;
            img.alt = 'Image attached to ' + card.dataset.title;
            img.loading = 'lazy';
            imageContainer.append(img);
        });
        setModalState(detailModal, true);
        document.getElementById('closeDetailBtn').focus();
    }

    document.getElementById('openAddDocBtn').addEventListener('click', function () { openEditor(null); });
    document.getElementById('fabAddBtn').addEventListener('click', function () { openEditor(null); });
    document.getElementById('detailEditBtn').addEventListener('click', function () {
        if (selectedCard) openEditor(selectedCard);
    });

    document.getElementById('docPinBtn').addEventListener('click', function () {
        pinnedState = !pinnedState;
        updatePinButton();
    });

    document.getElementById('docDeleteBtn').addEventListener('click', function () {
        if (window.confirm('Are you sure you want to delete this document activity?')) {
            document.getElementById('docDeleteForm').requestSubmit();
        }
    });

    document.getElementById('closeDetailBtn').addEventListener('click', function () { setModalState(detailModal, false); });
    document.getElementById('detailCloseBtn').addEventListener('click', function () { setModalState(detailModal, false); });
    document.getElementById('closeDocModalBtn').addEventListener('click', function () { setModalState(editorModal, false); });

    [detailModal, editorModal].forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) setModalState(modal, false);
        });
    });

    cards.forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.closest('button, form, input, label')) return;
            if (event.target.closest('.btn-card-edit')) {
                openEditor(card);
                return;
            }
            openDetails(card);
        });
        card.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && event.target === card) {
                event.preventDefault();
                openDetails(card);
            }
        });
        const editButton = card.querySelector('.btn-card-edit');
        editButton.addEventListener('click', function (event) {
            event.stopPropagation();
            openEditor(card);
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (deleteForm) {
        deleteForm.addEventListener('submit', function (event) {
            if (!window.confirm(deleteForm.dataset.confirm)) event.preventDefault();
        });
    });

    searchInput.addEventListener('input', function () {
        const query = searchInput.value.toLocaleLowerCase().trim();
        let visible = 0;
        cards.forEach(function (card) {
            const matches = [card.dataset.title, card.dataset.content, card.dataset.date]
                .some(function (value) { return value.toLocaleLowerCase().includes(query); });
            card.hidden = !matches;
            if (matches) visible += 1;
        });
        const hasPinned = Array.from(pinnedGrid.querySelectorAll('.note-card')).some(function (card) { return !card.hidden; });
        pinnedGrid.hidden = !hasPinned;
        pinnedLabel.hidden = !hasPinned;
        searchEmpty.hidden = visible !== 0 || cards.length === 0;
    });

    imagesInput.addEventListener('change', function () {
        previews.replaceChildren();
        Array.from(imagesInput.files).forEach(function (file) {
            if (!file.type.startsWith('image/')) return;
            const img = document.createElement('img');
            img.alt = file.name;
            img.src = URL.createObjectURL(file);
            img.onload = function () { URL.revokeObjectURL(img.src); };
            previews.append(img);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setModalState(detailModal, false);
            setModalState(editorModal, false);
        }
    });

    form.addEventListener('submit', function (event) {
        if (!form.reportValidity()) event.preventDefault();
    });
});
