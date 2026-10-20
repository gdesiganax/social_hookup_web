<?php

declare(strict_types=1);

function uploaded_file_error_message(int $error): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The file is larger than the server allows.',
        UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
        UPLOAD_ERR_PARTIAL => 'The file upload was interrupted.',
        UPLOAD_ERR_NO_TMP_DIR => 'The upload folder is unavailable.',
        UPLOAD_ERR_CANT_WRITE => 'The file could not be saved.',
        UPLOAD_ERR_EXTENSION => 'The file type is blocked by the server.',
    ];

    return $messages[$error] ?? 'The file could not be uploaded.';
}

function save_uploaded_media(string $field, string $bucket, bool $imagesOnly = false): ?array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(uploaded_file_error_message($error));
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Upload validation failed.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException('The uploaded file is empty.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);
    $allowed = [
        'image/jpeg' => ['type' => 'image', 'ext' => 'jpg', 'max' => 8 * 1024 * 1024],
        'image/png' => ['type' => 'image', 'ext' => 'png', 'max' => 8 * 1024 * 1024],
        'image/webp' => ['type' => 'image', 'ext' => 'webp', 'max' => 8 * 1024 * 1024],
        'image/gif' => ['type' => 'image', 'ext' => 'gif', 'max' => 8 * 1024 * 1024],
        'video/mp4' => ['type' => 'video', 'ext' => 'mp4', 'max' => 50 * 1024 * 1024],
        'video/webm' => ['type' => 'video', 'ext' => 'webm', 'max' => 50 * 1024 * 1024],
        'video/quicktime' => ['type' => 'video', 'ext' => 'mov', 'max' => 50 * 1024 * 1024],
    ];

    if (!isset($allowed[$mime]) || ($imagesOnly && $allowed[$mime]['type'] !== 'image')) {
        throw new RuntimeException($imagesOnly ? 'Upload a JPG, PNG, WEBP, or GIF image.' : 'Upload a JPG, PNG, WEBP, GIF, MP4, WEBM, or MOV file.');
    }

    $rules = $allowed[$mime];
    if ($size > $rules['max']) {
        throw new RuntimeException($rules['type'] === 'image' ? 'Images must be 8MB or smaller.' : 'Videos must be 50MB or smaller.');
    }

    $bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'media';
    $relativeDir = 'assets/uploads/' . $bucket . '/' . date('Y/m');
    $absoluteDir = APP_ROOT . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        throw new RuntimeException('The upload folder could not be created.');
    }

    $filename = bin2hex(random_bytes(18)) . '.' . $rules['ext'];
    $absolutePath = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }

    @chmod($absolutePath, 0644);

    return [
        'media_type' => $rules['type'],
        'media_url' => url($relativeDir . '/' . $filename),
        'mime_type' => $mime,
        'size' => $size,
    ];
}

function save_uploaded_verification_document(string $field): ?array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(uploaded_file_error_message($error));
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Document upload validation failed.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException('The uploaded document is empty.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);
    $allowed = [
        'application/pdf' => ['ext' => 'pdf', 'max' => 10 * 1024 * 1024],
        'image/jpeg' => ['ext' => 'jpg', 'max' => 8 * 1024 * 1024],
        'image/png' => ['ext' => 'png', 'max' => 8 * 1024 * 1024],
        'image/webp' => ['ext' => 'webp', 'max' => 8 * 1024 * 1024],
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Upload your Nigeria NIN document as a PDF, JPG, PNG, or WEBP file.');
    }

    $rules = $allowed[$mime];
    if ($size > $rules['max']) {
        throw new RuntimeException($mime === 'application/pdf' ? 'PDF documents must be 10MB or smaller.' : 'Image documents must be 8MB or smaller.');
    }

    $relativeDir = 'storage/verifications/' . date('Y/m');
    $absoluteDir = APP_ROOT . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0700, true)) {
        throw new RuntimeException('The verification storage folder could not be created.');
    }

    $filename = bin2hex(random_bytes(24)) . '.' . $rules['ext'];
    $absolutePath = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        throw new RuntimeException('The verification document could not be saved.');
    }

    @chmod($absolutePath, 0600);

    return [
        'path' => $relativeDir . '/' . $filename,
        'mime_type' => $mime,
        'original_name' => mb_substr((string) ($file['name'] ?? 'nin-document'), 0, 255),
        'size' => $size,
    ];
}
