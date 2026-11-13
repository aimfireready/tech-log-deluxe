<?php

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
});

it('stores an editor image and returns a root-relative url', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/attachments', ['file' => UploadedFile::fake()->image('rack.png', 40, 40)])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'url', 'mime', 'size']]);

    $attachment = Attachment::sole();
    expect($response->json('data.url'))->toBe('/storage/'.$attachment->path)
        ->and($attachment->path)->toStartWith('attachments/'.now()->format('Y/m').'/')
        ->and($attachment->mime)->toBe('image/png')
        ->and($attachment->user_id)->toBe($this->user->id)
        ->and($attachment->entry_id)->toBeNull();
    Storage::disk('public')->assertExists($attachment->path);
});

it('rejects svg and non-images', function (UploadedFile $file) {
    $this->actingAs($this->user)
        ->postJson('/attachments', ['file' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect(Attachment::count())->toBe(0);
})->with([
    'svg' => fn () => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'pdf' => fn () => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
]);

it('checks the real file content, not the name', function () {
    // Fake uploads report a type from their file name, so build a real file the way PHP receives one.
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, '<?php echo "pwned";');
    $disguised = new UploadedFile($path, 'innocent.png', 'image/png', null, true);

    $this->actingAs($this->user)
        ->postJson('/attachments', ['file' => $disguised])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect(Attachment::count())->toBe(0);
});

it('rejects images over 10 MB', function () {
    $this->actingAs($this->user)
        ->postJson('/attachments', ['file' => UploadedFile::fake()->image('big.jpg')->size(10241)])
        ->assertUnprocessable();
});

it('accepts uploads through the API', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/attachments', ['file' => UploadedFile::fake()->image('x.webp')])
        ->assertCreated()
        ->assertJsonPath('data.mime', 'image/webp');
});
