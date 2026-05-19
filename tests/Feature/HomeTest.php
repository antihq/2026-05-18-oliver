<?php

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('projects.github_org', 'antihq');
    Config::set('projects.cache_ttl', 21600);
    Config::set('services.github.token', null);
    Config::set('projects.repos', [
        ['title' => 'Test Project', 'repo' => '2026-05-18-test-repo'],
    ]);
    Cache::forget('github-projects');
    Project::clearBootedModels();
    @unlink(storage_path('framework/cache/sushi-app-models-project.sqlite'));

    Http::fake([
        'api.github.com/repos/antihq/2026-05-18-test-repo' => Http::response([
            'name' => '2026-05-18-test-repo',
            'description' => 'A test project',
            'html_url' => 'https://github.com/antihq/2026-05-18-test-repo',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 10,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);
});

afterEach(function () {
    Cache::forget('github-projects');
    Project::clearBootedModels();
    @unlink(storage_path('framework/cache/sushi-app-models-project.sqlite'));
});

test('home page is accessible without authentication', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('home page displays projects', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Test Project')
        ->assertSee('2026-05-18')
        ->assertSee('https://github.com/antihq/2026-05-18-test-repo')
        ->assertSee('https://2026-05-18-test-repo.antihq.com');
});
