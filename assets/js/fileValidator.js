// fileValidator.js

function validateFile(fileInput, options = {}) {
    const file = fileInput.files[0];
    const {
        allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx'],
        maxSize = 2 * 1024 * 1024 // 2MB default
    } = options;

    if (!file) {
        return { valid: false, error: 'No file selected.' };
    }

    const fileName = file.name;
    const fileSize = file.size;
    const fileExtension = fileName.split('.').pop().toLowerCase();
    const mimeType = file.type;

    const mimeMap = {
        pdf: 'application/pdf',
        jpg: 'image/jpeg',
        jpeg: 'image/jpeg',
        png: 'image/png',
        docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    };

    // Check file extension
    if (!allowedExtensions.includes(fileExtension)) {
        return { valid: false, error: `Invalid file extension: .${fileExtension}` };
    }

    // Check file size
    if (fileSize > maxSize) {
        return { valid: false, error: `File too large. Max size is ${maxSize / (1024 * 1024)}MB.` };
    }

    // Check MIME type
    if (mimeType !== mimeMap[fileExtension]) {
        return { valid: false, error: `MIME type doesn't match file extension.` };
    }

    return { valid: true };
}
