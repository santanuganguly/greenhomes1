<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectConfig;

class ProjectsManager extends Component
{
    public $projects;
    public $editingProjectId = null;
    public $editingConfigId  = null;

    // Project form
    public $projName        = '';
    public $projDescription = '';
    public $projLocation    = '';
    public $projActive      = true;

    // Config form
    public $cfgName      = '';
    public $cfgPrice     = '';
    public $cfgPriceType = 'Indicative Price';
    public $cfgActive    = true;
    public $cfgProjectId = null;

    public $successMsg = '';

    public function mount()
    {
        $this->loadProjects();
    }

    public function loadProjects()
    {
        $this->projects = Project::with('configs')->get();
    }

    public function editProject(int $id)
    {
        $p = Project::findOrFail($id);
        $this->editingProjectId = $id;
        $this->projName         = $p->name;
        $this->projDescription  = $p->description ?? '';
        $this->projLocation     = $p->location ?? '';
        $this->projActive       = $p->is_active;
    }

    public function saveProject()
    {
        $this->validate(['projName' => 'required|string']);
        Project::where('id', $this->editingProjectId)->update([
            'name'        => $this->projName,
            'description' => $this->projDescription ?: null,
            'location'    => $this->projLocation ?: null,
            'is_active'   => $this->projActive,
        ]);
        $this->successMsg = '✅ Project saved!';
        $this->editingProjectId = null;
        $this->loadProjects();
    }

    public function editConfig(int $id)
    {
        $c = ProjectConfig::findOrFail($id);
        $this->editingConfigId = $id;
        $this->cfgProjectId    = $c->project_id;
        $this->cfgName         = $c->config_name;
        $this->cfgPrice        = $c->price_label;
        $this->cfgPriceType    = $c->price_type;
        $this->cfgActive       = $c->is_active;
    }

    public function saveConfig()
    {
        $this->validate([
            'cfgName'  => 'required|string',
            'cfgPrice' => 'required|string',
        ]);
        ProjectConfig::where('id', $this->editingConfigId)->update([
            'config_name' => $this->cfgName,
            'price_label' => $this->cfgPrice,
            'price_type'  => $this->cfgPriceType,
            'is_active'   => $this->cfgActive,
        ]);
        $this->successMsg      = '✅ Configuration saved!';
        $this->editingConfigId = null;
        $this->loadProjects();
    }

    public function cancelEdit()
    {
        $this->editingProjectId = null;
        $this->editingConfigId  = null;
        $this->successMsg       = '';
    }

    public function render()
    {
        return view('livewire.projects-manager')->layout('layouts.app');
    }
}
