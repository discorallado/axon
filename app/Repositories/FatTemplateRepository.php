<?php

namespace App\Repositories;

use App\Models\FatTemplate;
use App\Models\FatTemplateSection;
use App\Models\FatTemplateItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

class FatTemplateRepository
{
    /**
     * Obtener plantilla con caché
     */
    public function getWithCache(int $id, int $ttl = 3600): ?FatTemplate
    {
        return Cache::remember(
            "fat_template.{$id}",
            $ttl,
            fn() => FatTemplate::with(['sections.items.children.children'])->find($id)
        );
    }

    /**
     * Limpiar caché de plantilla
     */
    public function clearCache(int $id): void
    {
        Cache::forget("fat_template.{$id}");
    }

    /**
     * Obtener items jerárquicos de una plantilla
     */
    public function getHierarchicalItems(int $templateId): Collection
    {
        return FatTemplateItem::whereHas('section', fn($q) => $q->where('template_id', $templateId))
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * Crear item con path materializado
     */
    public function createItem(array $data): FatTemplateItem
    {
        $section = FatTemplateSection::findOrFail($data['section_id']);
        $parent = isset($data['parent_id']) 
            ? FatTemplateItem::find($data['parent_id']) 
            : null;

        // Calcular path y depth
        $depth = $parent ? $parent->depth + 1 : 1;
        $path = $parent ? "{$parent->path}/{$data['code']}" : $data['code'];

        return FatTemplateItem::create([
            'section_id' => $data['section_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $data['code'],
            'path' => $path,
            'description' => $data['description'],
            'result_type' => $data['result_type'] ?? 'ternary',
            'result_config' => $data['result_config'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'allow_evidence' => $data['allow_evidence'] ?? true,
            'depth' => $depth,
            'order' => $data['order'] ?? 0,
        ]);
    }

    /**
     * Actualizar item y regenerar paths de hijos
     */
    public function updateItem(FatTemplateItem $item, array $data): bool
    {
        if (isset($data['code']) && $data['code'] !== $item->code) {
            // Regenerar path de este item y sus descendientes
            $newPath = $item->parent 
                ? str_replace($item->path, $item->parent->path . '/' . $data['code'], $item->path)
                : $data['code'];
            
            $data['path'] = $newPath;
        }

        $updated = $item->update($data);

        // Actualizar paths de hijos recursivamente
        if ($updated && isset($data['code'])) {
            $this->updateChildrenPaths($item);
        }

        // Limpiar caché
        $this->clearCache($item->section->template_id);

        return $updated;
    }

    /**
     * Actualizar paths de items hijos recursivamente
     */
    protected function updateChildrenPaths(FatTemplateItem $parent): void
    {
        $parent->children()->each(function (FatTemplateItem $child) use ($parent) {
            $newPath = "{$parent->path}/{$child->code}";
            $child->update(['path' => $newPath]);
            $this->updateChildrenPaths($child);
        });
    }

    /**
     * Eliminar item y limpiar caché
     */
    public function deleteItem(FatTemplateItem $item): bool
    {
        $templateId = $item->section->template_id;
        $deleted = $item->delete();
        
        if ($deleted) {
            $this->clearCache($templateId);
        }

        return $deleted;
    }

    /**
     * Obtener todos los items planos de una plantilla
     */
    public function getAllFlatItems(int $templateId): Collection
    {
        return FatTemplateItem::whereHas('section', fn($q) => $q->where('template_id', $templateId))
            ->orderBy('path')
            ->get();
    }

    /**
     * Contar items por tipo de resultado
     */
    public function countByResultType(int $templateId): array
    {
        return [
            'ternary' => $this->getAllFlatItems($templateId)
                ->where('result_type', 'ternary')
                ->count(),
            'numeric' => $this->getAllFlatItems($templateId)
                ->where('result_type', 'numeric')
                ->count(),
            'text' => $this->getAllFlatItems($templateId)
                ->where('result_type', 'text')
                ->count(),
        ];
    }
}
