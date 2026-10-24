<?php

// Standalone "your report was not saved" page, shared by every failure that
// ends a match report submission (bad photo, failed DB insert). The form
// hides itself on submit, so this is a fresh page with a way back rather
// than a message inside the form.
final class MatchReportErrorPage {
    public function __construct(
        private string $heading,
        private string $message,
        private string $formUrl,
        private string $reference
    ) {}

    public function render(): void {
        $heading = htmlspecialchars($this->heading);
        $message = htmlspecialchars($this->message);
        $formUrl = htmlspecialchars($this->formUrl);
        $reference = htmlspecialchars($this->reference);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $heading . '</title>
<link rel="stylesheet" href="/styles-gss88-match-report-form.css">
</head>
<body>
<main style="max-width:36rem;margin:2rem auto;padding:0 1rem;">
<h1>' . $heading . '</h1>
<p>' . $message . '</p>
<p>If this keeps happening, please contact the region and mention this time: ' . $reference . '.</p>
<p><button type="button" onclick="history.back()">Go back</button> <a href="' . $formUrl . '">Start a new report</a></p>
</main>
</body>
</html>';
    }
}
