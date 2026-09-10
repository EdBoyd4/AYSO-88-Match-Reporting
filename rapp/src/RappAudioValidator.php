<?php
declare(strict_types=1);

/**
 * Validates an uploaded voice file. The allow-list is deliberately wide so that
 * essentially any phone or computer can submit *something*:
 *
 *   - iOS (Voice Memos / file picker)      .m4a  audio/mp4, audio/x-m4a
 *   - Android Chrome MediaRecorder          .webm audio/webm ; .ogg audio/ogg
 *   - Android file picker / older devices   .3gp audio/3gpp ; .amr audio/amr
 *   - Desktop browsers / uploads            .mp3 .wav .aac .ogg .opus .flac
 *
 * Depth of inspection matches gss88's game-card photo handling: MIME (via
 * finfo) + extension + size. Container-level parsing would need a third-party
 * library and isn't warranted for the initial release.
 */
final class RappAudioValidator
{
    /** @var array<string, list<string>> mime => allowed extensions */
    private const ALLOWED = [
        'audio/mpeg'      => ['mp3'],
        'audio/mp3'       => ['mp3'],
        'audio/mp4'       => ['m4a', 'mp4', 'aac'],
        'audio/x-m4a'     => ['m4a'],
        'audio/aac'       => ['aac', 'm4a'],
        'audio/aacp'      => ['aac'],
        'audio/wav'       => ['wav'],
        'audio/x-wav'     => ['wav'],
        'audio/wave'      => ['wav'],
        'audio/vnd.wave'  => ['wav'],
        'audio/ogg'       => ['ogg', 'oga', 'opus'],
        'audio/opus'      => ['opus', 'ogg'],
        'application/ogg' => ['ogg', 'oga'],
        'audio/webm'      => ['webm'],
        'video/webm'      => ['webm'], // some recorders label an audio-only webm this way
        'audio/3gpp'      => ['3gp', '3gpp'],
        'audio/3gpp2'     => ['3g2'],
        'audio/amr'       => ['amr'],
        'audio/flac'      => ['flac'],
        'audio/x-flac'    => ['flac'],
        'audio/x-caf'     => ['caf'],
    ];

    public function __construct(private int $maxBytes) {}

    /**
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file a $_FILES entry
     * @return array{mime:string, ext:string, bytes:int, original_name:string}
     * @throws RappAudioException on any problem, with a user-safe message
     */
    public function validate(array $file): array
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RappAudioException('That audio file is too large.');
        }
        if ($err === UPLOAD_ERR_PARTIAL) {
            throw new RappAudioException('The upload was interrupted. Please try again.');
        }
        if ($err !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RappAudioException('No audio file was received.');
        }

        $bytes = (int) ($file['size'] ?? filesize($file['tmp_name']) ?: 0);
        if ($bytes <= 0) {
            throw new RappAudioException('The audio file is empty.');
        }
        if ($bytes > $this->maxBytes) {
            throw new RappAudioException(sprintf(
                'That audio file is %.1f MB; the limit is %.0f MB.',
                $bytes / 1048576,
                $this->maxBytes / 1048576
            ));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($file['tmp_name']));
        if (!isset(self::ALLOWED[$mime])) {
            error_log('RAPP audio rejected, MIME: ' . $mime);
            throw new RappAudioException('That file type is not a recognised audio recording.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::ALLOWED[$mime], true)) {
            // Trust the detected MIME over a missing/odd extension: pick the
            // canonical one for that type.
            $ext = self::ALLOWED[$mime][0];
        }

        return [
            'mime' => $mime,
            'ext' => $ext,
            'bytes' => $bytes,
            'original_name' => (string) ($file['name'] ?? ''),
        ];
    }
}

final class RappAudioException extends RuntimeException
{
}
