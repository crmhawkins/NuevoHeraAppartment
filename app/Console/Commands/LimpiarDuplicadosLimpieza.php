<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApartamentoLimpiezaItem;
use Illuminate\Support\Facades\DB;

class LimpiarDuplicadosLimpieza extends Command
{
    protected $signature = 'limpieza:limpiar-duplicados {limpieza_id}';
    protected $description = 'Limpiar duplicados en items de limpieza';

    public function handle()
    {
        $limpiezaId = $this->argument('limpieza_id');
        
        $this->info("🔍 Analizando limpieza #{$limpiezaId}...");
        
        // Contar items antes
        $totalAntes = ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)->count();
        $this->info("📊 Total de items antes: {$totalAntes}");
        
        // Encontrar duplicados
        $duplicados = ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
            ->selectRaw('item_id, checklist_id, COUNT(*) as count')
            ->groupBy('item_id', 'checklist_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
            
        $this->info("🚨 Grupos con duplicados encontrados: {$duplicados->count()}");
        
        if ($duplicados->isEmpty()) {
            $this->info("✅ No hay duplicados que limpiar");
            return;
        }
        
        // Mostrar duplicados
        foreach ($duplicados as $dup) {
            $this->warn("   Checklist {$dup->checklist_id}, Item {$dup->item_id}: {$dup->count} copias");
        }
        
        if (!$this->confirm('¿Deseas proceder con la limpieza?')) {
            $this->info("❌ Operación cancelada");
            return;
        }
        
        $this->info("🧹 Limpiando duplicados...");
        
        // Eliminar duplicados manteniendo solo uno por grupo
        $eliminados = 0;
        
        foreach ($duplicados as $dup) {
            $items = ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
                ->where('item_id', $dup->item_id)
                ->where('checklist_id', $dup->checklist_id)
                ->orderBy('id')
                ->get();
                
            // Mantener el primero, eliminar el resto
            $items->skip(1)->each(function ($item) use (&$eliminados) {
                $item->delete();
                $eliminados++;
            });
        }
        
        // Contar items después
        $totalDespues = ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)->count();
        
        $this->info("✅ Limpieza completada:");
        $this->info("   📊 Items eliminados: {$eliminados}");
        $this->info("   📊 Total antes: {$totalAntes}");
        $this->info("   📊 Total después: {$totalDespues}");
        $this->info("   📊 Diferencia: " . ($totalAntes - $totalDespues));
        
        // Verificar items únicos por checklist
        $this->info("🔍 Verificando items únicos por checklist:");
        $itemsUnicos = ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
            ->selectRaw('checklist_id, COUNT(DISTINCT item_id) as unique_items')
            ->groupBy('checklist_id')
            ->get();
            
        foreach ($itemsUnicos as $item) {
            $checklistName = $item->checklist_id ?: 'Principal';
            $this->info("   📋 Checklist {$checklistName}: {$item->unique_items} items únicos");
        }
    }
}
