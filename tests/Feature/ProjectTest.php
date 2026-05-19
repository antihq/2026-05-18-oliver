<?php

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('projects.github_org', 'antihq');
    Config::set('projects.cache_ttl', 21600);
    Config::set('services.github.token', null);
    Config::set('projects.repos', []);
    Cache::forget('github-projects');
    Project::clearBootedModels();
    @unlink(storage_path('framework/cache/sushi-app-models-project.sqlite'));
});

afterEach(function () {
    Cache::forget('github-projects');
    Project::clearBootedModels();
    @unlink(storage_path('framework/cache/sushi-app-models-project.sqlite'));
});

test('getRows returns empty array when no repos configured', function () {
    Config::set('projects.repos', []);

    $rows = (new Project)->getRows();

    expect($rows)->toBe([]);
});

test('getRows fetches and maps repos from GitHub API', function () {
    Config::set('projects.repos', [
        ['title' => 'My App', 'repo' => 'my-app'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/my-app' => Http::response([
            'name' => 'my-app',
            'description' => 'A cool app',
            'html_url' => 'https://github.com/antihq/my-app',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 42,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows)->toHaveCount(1);
    expect($rows[0])->toBe([
        'id' => 1,
        'title' => 'My App',
        'project_date' => '',
        'description' => 'A cool app',
        'github_url' => 'https://github.com/antihq/my-app',
        'website' => 'https://my-app.antihq.com',
        'language' => 'PHP',
        'stars' => 42,
        'pushed_at' => '2026-05-18T00:00:00Z',
    ]);
});

test('getRows auto-generates website URL from repo and org', function () {
    Config::set('projects.repos', [
        ['title' => 'Test', 'repo' => '2026-05-18-mayfly'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/2026-05-18-mayfly' => Http::response([
            'name' => '2026-05-18-mayfly',
            'description' => 'Test repo',
            'html_url' => 'https://github.com/antihq/2026-05-18-mayfly',
            'homepage' => '',
            'language' => 'TypeScript',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows[0]['website'])->toBe('https://2026-05-18-mayfly.antihq.com');
});

test('getRows extracts project_date from date-prefixed repo name', function () {
    Config::set('projects.repos', [
        ['title' => 'Mayfly', 'repo' => '2026-05-18-mayfly'],
        ['title' => 'Simple', 'repo' => 'simple-project'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/2026-05-18-mayfly' => Http::response([
            'name' => '2026-05-18-mayfly',
            'description' => '',
            'html_url' => 'https://github.com/antihq/2026-05-18-mayfly',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
        'api.github.com/repos/antihq/simple-project' => Http::response([
            'name' => 'simple-project',
            'description' => '',
            'html_url' => 'https://github.com/antihq/simple-project',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows[0]['project_date'])->toBe('2026-05-18');
    expect($rows[1]['project_date'])->toBe('');
});

test('getRows uses custom website when provided in config', function () {
    Config::set('projects.repos', [
        ['title' => 'Test', 'repo' => 'my-repo', 'website' => 'https://custom.dev'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/my-repo' => Http::response([
            'name' => 'my-repo',
            'description' => '',
            'html_url' => 'https://github.com/antihq/my-repo',
            'homepage' => 'https://ignore-this.com',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows[0]['website'])->toBe('https://custom.dev');
});

test('getRows uses GitHub homepage when no custom website', function () {
    Config::set('projects.repos', [
        ['title' => 'Test', 'repo' => 'my-repo'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/my-repo' => Http::response([
            'name' => 'my-repo',
            'description' => '',
            'html_url' => 'https://github.com/antihq/my-repo',
            'homepage' => 'https://my-repo-site.com',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows[0]['website'])->toBe('https://my-repo-site.com');
});

test('getRows sorts projects by pushed_at descending', function () {
    Config::set('projects.repos', [
        ['title' => 'Old', 'repo' => 'old-project'],
        ['title' => 'New', 'repo' => 'new-project'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/old-project' => Http::response([
            'name' => 'old-project',
            'description' => '',
            'html_url' => 'https://github.com/antihq/old-project',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-01-01T00:00:00Z',
        ]),
        'api.github.com/repos/antihq/new-project' => Http::response([
            'name' => 'new-project',
            'description' => '',
            'html_url' => 'https://github.com/antihq/new-project',
            'homepage' => '',
            'language' => 'TypeScript',
            'stargazers_count' => 5,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    $rows = (new Project)->getRows();

    expect($rows[0]['title'])->toBe('New');
    expect($rows[1]['title'])->toBe('Old');
});

test('getRows falls back gracefully on API failure', function () {
    Config::set('projects.repos', [
        ['title' => 'Gone', 'repo' => 'deleted-repo'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/deleted-repo' => Http::response([], 404),
    ]);

    $rows = (new Project)->getRows();

    expect($rows)->toHaveCount(1);
    expect($rows[0]['title'])->toBe('Gone');
    expect($rows[0]['github_url'])->toBe('https://github.com/antihq/deleted-repo');
    expect($rows[0]['website'])->toBe('https://deleted-repo.antihq.com');
    expect($rows[0]['stars'])->toBe(0);
    expect($rows[0]['language'])->toBe('');
});

test('getRows caches API results', function () {
    Config::set('projects.repos', [
        ['title' => 'Cached', 'repo' => 'cached-repo'],
    ]);

    Http::fake([
        'api.github.com/repos/antihq/cached-repo' => Http::response([
            'name' => 'cached-repo',
            'description' => 'Cached',
            'html_url' => 'https://github.com/antihq/cached-repo',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 1,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    (new Project)->getRows();
    (new Project)->getRows();

    Http::assertSentCount(1);
});

test('fetchRepo sends auth token when configured', function () {
    Config::set('services.github.token', 'ghp_test123');

    Http::fake([
        'api.github.com/*' => Http::response([
            'name' => 'test',
            'description' => '',
            'html_url' => 'https://github.com/antihq/test',
            'homepage' => '',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'pushed_at' => '2026-05-18T00:00:00Z',
        ]),
    ]);

    Config::set('projects.repos', [
        ['title' => 'Test', 'repo' => 'test'],
    ]);

    (new Project)->getRows();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'token ghp_test123');
    });
});
