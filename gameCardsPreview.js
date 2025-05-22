async function scaleCardPhoto(file, dimensions, maxSize) {
    // Ensure the file is an image
    if (!file.type.match(/image.*/)) return null;

    const image = new Image();
    image.src = URL.createObjectURL(file);

    await new Promise((res) => image.onload = res);
    const canvas = document.createElement("canvas");
    const context = canvas.getContext("2d", { alpha: true });

    let width = image.width;
    let height = image.height;
    let scale = 1.0;

    // Calculate scale to fit the image within maxSize
    const scaleFactor = Math.sqrt(file.size / maxSize);
    if (scaleFactor > 1) {
        scale = 1 / scaleFactor;
        width *= scale;
        height *= scale;
    }

    canvas.width = width;
    canvas.height = height;
    context.drawImage(image, 0, 0, width, height);

    let quality = 0.9; // Initial quality value
    let blob = null;

    // Try different quality levels until the resulting image is less than maxSize
    while (quality > 0.1 && (blob === null || blob.size > maxSize)) {
        blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', quality));
        quality -= 0.1;
    }

    return blob;
}

async function displayPhoto() {
    var photoHolder = document.getElementById(this.id+'-preview');
    // Check if any file is selected
    if (this.files && this.files[0]) {
        const file = this.files[0];
        const dimensions = { width: '700px', height: '500px' }; // Example dimensions
        const maxSize = 2 * 1024 * 1024; // 2MB

        try {
            const resizedImage = await scaleCardPhoto(file, dimensions, maxSize);
            if (resizedImage !== null) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(resizedImage);
                img.style.maxWidth = '700px'; // Set maximum width
                img.style.maxHeight = '500px'; // Set maximum height
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
        // If no file is selected, clear the preview
        photoHolder.innerHTML = '';
    }
}

function photoPreviewer(photo) {
    // Add an event listener to the input element
    photo.addEventListener('change', displayPhoto);
}