<?php

use LaravelEnso\Core\app\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\Core\app\Classes\DefaultPreferences;
use LaravelEnso\Core\app\Models\Preference;
use LaravelEnso\Localisation\app\Models\Language;
use LaravelEnso\TestHelper\app\Traits\SignIn;
use LaravelEnso\TestHelper\app\Traits\TestCreateForm;
use LaravelEnso\TestHelper\app\Traits\TestDataTable;
use Tests\TestCase;

class LocalisationTest extends TestCase
{
    use RefreshDatabase, SignIn, TestDataTable, TestCreateForm;
    const NAME = 'xx';
    private $faker;
    private $prefix = 'system.localisation';

    protected function setUp()
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->seed()
            ->signIn(User::first());

        $this->faker = Factory::create();
    }

    /** @test */
    public function store()
    {
        $response = $this->post(
            route('system.localisation.store', [], false),
            $this->postParams()
        );

        $language = Language::whereName(self::NAME)->first();

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => __('The language was successfully created'),
                'redirect' => 'system.localisation.edit',
                'id' => $language->id,
            ]);

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name))
        );

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name.'.json'))
        );

        $this->cleanUp($language);
    }

    /** @test */
    public function edit()
    {
        $language = $this->createLanguage();

        $this->get(route('system.localisation.edit', $language->id, false))
            ->assertStatus(200)
            ->assertJsonStructure(['form']);

        $this->cleanUp($language);
    }

    /** @test */
    public function update()
    {
        $this->post(
            route('system.localisation.store', [], false),
            $this->postParams()
        );
        $language = Language::whereName(self::NAME)->first();

        $language->name = 'zz';

        $this->patch(
            route('system.localisation.update', $language->id, false),
            $language->toArray() + ['flag_sufix' => $language->name]
        )->assertStatus(200)
            ->assertJson([
                'message' => __('The language was successfully updated'),
            ]);

        $this->assertEquals('zz', $language->fresh()->name);

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name))
        );

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name.'.json'))
        );

        $this->cleanUp($language);
    }

    /** @test */
    public function destroy()
    {
        $this->post(
            route('system.localisation.store', [], false),
            $this->postParams()
        );

        $language = Language::whereName(self::NAME)->first();
        $languageName = $language->name;

        $this->delete(
            route('system.localisation.destroy', $language->id, false)
        )->assertStatus(200)
            ->assertJson([
                'message' => __('The language was successfully deleted'),
                'redirect' => 'system.localisation.index',
            ]);

        $this->assertFalse(
            \File::exists(resource_path('lang/'.$languageName))
        );

        $this->assertFalse(
            \File::exists(resource_path('lang/'.$languageName.'.json'))
        );
    }

    /** @test */
    public function cant_destroy_default_language()
    {
        $language = $this->createLanguage();

        config()->set('app.fallback_locale', $language->name);

        $this->delete(route('system.localisation.destroy', $language->id, false))
            ->assertStatus(403);

        $this->assertNotNull($language->fresh());
    }

    /** @test */
    public function cant_destroy_if_language_is_in_use()
    {
        $this->post(
            route('system.localisation.store', [], false),
            $this->postParams()
        );

        $language = Language::whereName(self::NAME)->first();

        $this->setLanguage($language);

        $this->delete(route('system.localisation.destroy', $language->id, false))
            ->assertStatus(403);

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name))
        );

        $this->assertTrue(
            \File::exists(resource_path('lang/'.$language->name.'.json'))
        );

        $this->cleanUp($language);
    }

    private function createLanguage()
    {
        return Language::create($this->postParams());
    }

    private function postParams()
    {
        return [
            'display_name' => strtolower($this->faker->country),
            'name' => self::NAME,
            'flag_sufix' => self::NAME,
            'flag' => 'flag-icon flag-icon-'.self::NAME,
        ];
    }

    private function setLanguage($language)
    {
        $preferences = DefaultPreferences::data();
        $preferences->global->lang = $language->name;
        $preference = new Preference(['value' => $preferences]);
        $preference->user_id = 1;
        $preference->save();
    }

    private function cleanUp($language)
    {
        \File::delete(
            resource_path('lang'.DIRECTORY_SEPARATOR.$language->name.'.json')
        );

        \File::delete(
            resource_path('lang/app'.DIRECTORY_SEPARATOR.$language->name.'.json')
        );

        \File::delete(
            resource_path('lang/enso'.DIRECTORY_SEPARATOR.$language->name.'.json')
        );

        \File::deleteDirectory(
            resource_path('lang'.DIRECTORY_SEPARATOR.$language->name)
        );
    }
}
