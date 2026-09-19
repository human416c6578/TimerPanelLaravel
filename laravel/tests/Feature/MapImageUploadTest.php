<?php

use App\Livewire\MapImageUpload;
use App\Models\User;
use App\Services\MapImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function pngFile(string $name = 'map.png'): UploadedFile
{
    // A real 1x1 PNG. UploadedFile::fake()->image() needs the GD extension, which this image does not carry.
    return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
}

beforeEach(function () {
    Storage::fake('local');
    // The picker lists the game's maps, read through this cache.
    Cache::put('map_names', ['bhop_arcane', 'bhop_eazy'], 600);
});

test('a visitor who is not signed in cannot open the uploader', function () {
    Livewire::test(MapImageUpload::class)->assertForbidden();
});

test('nor can a visitor call its actions', function () {
    // The component was mounted by an admin, then the session ended: the action still checks.
    $component = Livewire::actingAs(User::factory()->create())->test(MapImageUpload::class);

    auth()->logout();

    $component->call('remove', 'bhop_arcane')->assertForbidden();
});

test('a file that is not an image is refused before anything is saved', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(MapImageUpload::class)
        ->set('map', 'bhop_arcane')
        ->set('photo', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('photo');

    expect(app(MapImages::class)->find('bhop_arcane'))->toBeNull();
});

test('an oversized picture is refused', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(MapImageUpload::class)
        ->set('map', 'bhop_arcane')
        ->set('photo', UploadedFile::fake()->create('big.png', 5000, 'image/png'))
        ->call('save')
        ->assertHasErrors('photo');
});

test('a map has to be chosen', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(MapImageUpload::class)
        ->set('photo', pngFile('a.png'))
        ->call('save')
        ->assertHasErrors('map');
});

test('a picture can be saved, replaced and removed', function () {
    $images = app(MapImages::class);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    expect($images->save('bhop_arcane', $png))->toBe('map-images/bhop_arcane.png')
        ->and($images->find('bhop_arcane'))->toBe('map-images/bhop_arcane.png')
        ->and($images->count())->toBe(1);

    $images->forget('bhop_arcane');

    expect($images->find('bhop_arcane'))->toBeNull()->and($images->count())->toBe(0);
});

test('bytes that are not an image are not kept, whatever they are called', function () {
    expect(app(MapImages::class)->save('bhop_arcane', '<?php echo 1; ?>'))->toBeNull();
    Storage::disk('local')->assertDirectoryEmpty('map-images');
});

test('a picture dropped into the folder by hand is served without any source configured', function () {
    config(['maps.image_sources' => []]);

    Storage::disk('local')->put('map-images/bhop_eazy.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

    $this->get('/map-images/bhop_eazy')->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->blade('<x-cover name="bhop_eazy" />')->assertSee('/map-images/bhop_eazy', false);
    $this->blade('<x-cover name="bhop_other" />')->assertDontSee('/map-images/', false);
});

test('an admin can give a map a picture', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(MapImageUpload::class)
        ->set('map', 'bhop_arcane')
        ->set('photo', pngFile('arcane.png'))
        ->call('save')
        ->assertHasNoErrors();

    expect(app(MapImages::class)->find('bhop_arcane'))->toBe('map-images/bhop_arcane.png');
});

test('a picture cannot be given to a name that is not a map', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(MapImageUpload::class)
        ->set('map', '../../.env')
        ->set('photo', pngFile('x.png'))
        ->call('save')
        ->assertHasErrors('map');

    Storage::disk('local')->assertDirectoryEmpty('map-images');
});
