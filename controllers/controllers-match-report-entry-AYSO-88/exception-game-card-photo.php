<?php

// Thrown by the game card photo checks when a photo can't be accepted.
// getMessage() is written for the person submitting the form (it is shown
// to them verbatim), so keep it plain-language and say what to do next.
final class GameCardPhotoException extends RuntimeException {}
