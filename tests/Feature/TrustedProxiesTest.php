<?php

function forwardedRequestIsSecure($test): bool
{
    $test->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get('/login')
        ->assertOk();

    return request()->isSecure();
}

it('ignores X-Forwarded headers by default', function () {
    expect(forwardedRequestIsSecure($this))->toBeFalse();
});

it('believes X-Forwarded headers from a trusted proxy', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8']);

    expect(forwardedRequestIsSecure($this))->toBeTrue();
});

it('ignores X-Forwarded headers from other addresses', function () {
    config(['trustedproxy.proxies' => '192.168.1.1']);

    expect(forwardedRequestIsSecure($this))->toBeFalse();
});
