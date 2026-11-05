<?php

use App\Http\Requests\Api\V1\StoreSubmissionRequest;

it('normalizes valid mexican numbers to E.164', function (string $input) {
    expect(StoreSubmissionRequest::normalizePhone($input))->toBe('+525512345678');
})->with(['5512345678', '55 1234 5678', '(55) 1234-5678', '+52 55 1234 5678', '52 5512345678', '55-1234-5678']);

it('returns null for empty input', function () {
    expect(StoreSubmissionRequest::normalizePhone('  ( ) - '))->toBeNull();
});

it('leaves unrecognized numbers untouched so validation rejects them', function (string $input) {
    expect(StoreSubmissionRequest::normalizePhone($input))->toBe($input)
        ->and(preg_match(StoreSubmissionRequest::PHONE_PATTERN, $input))->toBe(0);
})->with(['12345', '+1 415 555 0100', '55123456789', 'teléfono']);
