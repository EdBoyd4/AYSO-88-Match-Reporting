async function scaleCardPhoto(file, dimensions, maxSize) {
    if (!file.type.match(/image.*/)) return null;

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    // Safely load the image, handle errors, and prevent memory leaks
    await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = () => reject(new Error('Image load failed'));
        image.src = objectUrl;
    }).finally(() => {
        URL.revokeObjectURL(objectUrl);
    });

    const canvas = document.createElement("canvas");
    // alpha: false is more performant since we export to JPEG
    const context = canvas.getContext("2d", { alpha: false });

    let width = image.width;
    let height = image.height;
    let scale = 1.0;

    // Estimate scale based on file size limit
    const scaleFactor = Math.sqrt(file.size / maxSize);
    if (scaleFactor > 1) {
        scale = 1 / scaleFactor;
        width = Math.floor(width * scale);
        height = Math.floor(height * scale);
    }

    canvas.width = width;
    canvas.height = height;

    // Fill with white to prevent transparent PNG backgrounds from turning black in JPEG
    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, width, height);
    context.drawImage(image, 0, 0, width, height);

    let quality = 0.9;
    let blob = null;
    let attempts = 0;

    // Compress in a loop, taking larger steps to reduce browser freezing, max 4 attempts
    while (quality > 0.1 && (blob === null || blob.size > maxSize) && attempts < 4) {
        blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', quality));
        quality -= 0.2; 
        attempts++;
    }

    return blob;
}
