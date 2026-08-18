async function displayPhoto() {
    const photoHolder = document.getElementById(this.id + '-preview');

    // Check if any file is selected
    if (this.files && this.files[0]) {
        const file = this.files[0];

        // A selection can come through empty (an interrupted picker, a
        // 0-byte file, etc.) - catch that here instead of letting it reach
        // the server, which currently hard-fails the whole page on an
        // unrecognizable file rather than showing a friendly message.
        if (file.size === 0) {
            console.warn('Selected game card photo "' + file.name + '" is empty (0 bytes); clearing it.');
            this.value = '';
            const existingImg = photoHolder.querySelector('img');
            if (existingImg && existingImg.src.startsWith('blob:')) {
                URL.revokeObjectURL(existingImg.src);
            }
            photoHolder.innerHTML = '<p class="photo-error">That file appears to be empty. Please choose a different photo.</p>';
            return;
        }

        const dimensions = { width: '700px', height: '500px' };
        const maxSize = 2 * 1024 * 1024; // 2MB

        try {
            const resizedImage = await scaleCardPhoto(file, dimensions, maxSize);

            if (resizedImage !== null && resizedImage.size > 0) {
                // Swap the raw file with the compressed file in the actual input!
                const dataTransfer = new DataTransfer();
                // Ensure proper filename extension for a JPEG
                const newFileName = file.name.replace(/\.[^/.]+$/, "") + "_compressed.jpg";
                dataTransfer.items.add(new File([resizedImage], newFileName, { type: 'image/jpeg' }));
                this.files = dataTransfer.files;

                // Clean up the old Object URL to prevent memory leaks
                const existingImg = photoHolder.querySelector('img');
                if (existingImg && existingImg.src.startsWith('blob:')) {
                    URL.revokeObjectURL(existingImg.src);
                }

                const img = document.createElement('img');
                img.src = URL.createObjectURL(resizedImage);
                // Sizing lives in CSS (.section_photo-gamecards-preview img)
                // so it can cap width to the actual container instead of a
                // fixed pixel value that can overflow it.
                img.alt = 'Game card preview';

                // Clear previous preview, if any
                photoHolder.innerHTML = '';
                // Append the image to the preview div
                photoHolder.appendChild(img);
            } else {
                // Resizing failed (or produced nothing usable) - fall back
                // to whatever was originally selected rather than silently
                // swapping in something empty; this.files is left as-is.
                console.log('Failed to resize image; keeping the original file.');
            }
        } catch (error) {
            console.error('Error resizing image:', error);
        }
    } else {
        // If no file is selected, clear the preview and revoke any existing URL
        const existingImg = photoHolder.querySelector('img');
        if (existingImg && existingImg.src.startsWith('blob:')) {
            URL.revokeObjectURL(existingImg.src);
        }
        photoHolder.innerHTML = '';
    }
}

function photoPreviewer(photo) {
    // Add an event listener to the input element
    photo.addEventListener('change', displayPhoto);
}