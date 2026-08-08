async function displayPhoto() {
    const photoHolder = document.getElementById(this.id + '-preview');

    // Check if any file is selected
    if (this.files && this.files[0]) {
        const file = this.files[0];
        const dimensions = { width: '700px', height: '500px' };
        const maxSize = 2 * 1024 * 1024; // 2MB

        try {
            const resizedImage = await scaleCardPhoto(file, dimensions, maxSize);

            if (resizedImage !== null) {
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
                img.style.maxWidth = '700px'; // Set maximum width
                img.style.maxHeight = '500px'; // Set maximum height
                img.alt = 'Game card preview';

                // Clear previous preview, if any
                photoHolder.innerHTML = '';
                // Append the image to the preview div
                photoHolder.appendChild(img);
            } else {
                console.log('Failed to resize image.');
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