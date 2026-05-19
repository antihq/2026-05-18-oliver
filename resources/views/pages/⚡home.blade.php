<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.home')] #[Title('Home')] class extends Component
{
    #[Computed]
    public function projects()
    {
        return Project::all();
    }
}; ?>

<div class="mx-auto max-w-2xl">
    <flux:heading level="1" size="xl">
        Oliver Servin
    </flux:heading>

    <flux:text class="mt-2" size="lg">
        Building things at <flux:link href="https://github.com/antihq" :accent="false" target="_blank">antihq</flux:link>.
    </flux:text>

    <flux:separator class="my-8" />

    <flux:heading level="2">Projects &amp; Experiments</flux:heading>

    @if($this->projects->isNotEmpty())
        <div class="mt-6 space-y-8">
            @foreach($this->projects as $project)
                <div>
                    <flux:heading level="3" size="sm">
                        @if($project->project_date)
                            <flux:badge size="sm" color="zinc" class="mr-2 font-mono">{{ $project->project_date }}</flux:badge>
                        @endif
                        {{ $project->title }}
                    </flux:heading>

                    @if($project->description)
                        <flux:text class="mt-2" size="sm">
                            {{ $project->description }}
                        </flux:text>
                    @endif

                    <x-description.list class="mt-4">
                        @if($project->language)
                            <x-description.term>Language</x-description.term>
                            <x-description.details>{{ $project->language }}</x-description.details>
                        @endif

                        <x-description.term>Updated</x-description.term>
                        <x-description.details>{{ \Carbon\Carbon::parse($project->pushed_at)->diffForHumans() }}</x-description.details>

                        <x-description.term>Links</x-description.term>
                        <x-description.details>
                            <div class="flex items-center gap-3">
                                @if($project->website)
                                    <flux:link :href="$project->website" :accent="false" target="_blank">Website</flux:link>
                                @endif
                                <flux:link :href="$project->github_url" :accent="false" target="_blank">GitHub</flux:link>
                            </div>
                        </x-description.details>
                    </x-description.list>
                </div>
            @endforeach
        </div>
    @else
        <flux:text class="mt-4" variant="subtle">
            No projects yet. Add repos to <code class="text-xs">config/projects.php</code> to get started.
        </flux:text>
    @endif
</div>
