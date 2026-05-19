<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Sushi\Sushi;

class Project extends Model
{
    use Sushi;

    protected $schema = [
        'id' => 'integer',
        'title' => 'string',
        'project_date' => 'string',
        'description' => 'string',
        'github_url' => 'string',
        'website' => 'string',
        'language' => 'string',
        'stars' => 'integer',
        'pushed_at' => 'dateTime',
    ];

    public function getRows()
    {
        return Cache::remember('github-projects', now()->addSeconds((int) config('projects.cache_ttl', 21600)), function () {
            $org = config('projects.github_org', 'antihq');
            $token = config('services.github.token');
            $repos = config('projects.repos', []);

            if (empty($repos)) {
                return [];
            }

            return collect($repos)->map(function (array $project, int $index) use ($org, $token) {
                $response = $this->fetchRepo($org, $project['repo'], $token);
                $projectDate = preg_match('/^\d{4}-\d{2}-\d{2}/', $project['repo'], $m) ? $m[0] : '';

                if ($response === null) {
                    return [
                        'id' => $index + 1,
                        'title' => $project['title'] ?? $project['repo'],
                        'project_date' => $projectDate,
                        'description' => $project['description'] ?? '',
                        'github_url' => "https://github.com/{$org}/{$project['repo']}",
                        'website' => $project['website'] ?? "https://{$project['repo']}.{$org}.com",
                        'language' => '',
                        'stars' => 0,
                        'pushed_at' => now()->toDateTimeString(),
                    ];
                }

                return [
                    'id' => $index + 1,
                    'title' => $project['title'] ?? $response['name'],
                    'project_date' => $projectDate,
                    'description' => $response['description'] ?? '',
                    'github_url' => $response['html_url'],
                    'website' => $project['website'] ?? ($response['homepage'] ?: "https://{$project['repo']}.{$org}.com"),
                    'language' => $response['language'] ?? '',
                    'stars' => $response['stargazers_count'] ?? 0,
                    'pushed_at' => $response['pushed_at'] ?? now()->toDateTimeString(),
                ];
            })->sortByDesc('pushed_at')->values()->toArray();
        });
    }

    protected function sushiShouldCache(): bool
    {
        return true;
    }

    protected function fetchRepo(string $org, string $repo, ?string $token): ?array
    {
        try {
            $headers = [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => Str::slug(config('app.name', 'laravel')),
            ];

            if ($token) {
                $headers['Authorization'] = "token {$token}";
            }

            $response = Http::withHeaders($headers)
                ->get("https://api.github.com/repos/{$org}/{$repo}");

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
