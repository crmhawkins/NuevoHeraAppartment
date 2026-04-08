<?php

namespace App\Console\Commands;

use App\Models\BankinterCredential;
use Illuminate\Console\Command;

/**
 * Migra las credenciales Bankinter desde config('services.bankinter.cuentas')
 * a la tabla bankinter_credentials. Si un alias ya existe en BD, se salta
 * salvo que se pase --force, en cuyo caso se actualiza.
 */
class MigrarBankinterCredencialesCommand extends Command
{
    protected $signature = 'bankinter:migrar-credenciales {--force : Sobrescribe credenciales existentes}';

    protected $description = 'Migra credenciales Bankinter de config/.env a la tabla bankinter_credentials';

    public function handle(): int
    {
        $cuentas = config('services.bankinter.cuentas', []);

        if (empty($cuentas)) {
            $this->warn('No hay cuentas definidas en config(services.bankinter.cuentas).');
            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');

        $resumen = [];
        $creados = 0;
        $actualizados = 0;
        $saltados = 0;
        $invalidos = 0;

        foreach ($cuentas as $alias => $config) {
            $user = $config['user'] ?? null;
            $password = $config['password'] ?? null;

            if (empty($user) || empty($password)) {
                $resumen[] = [$alias, '-', '-', 'invalido (user/password vacios)'];
                $invalidos++;
                continue;
            }

            $existing = BankinterCredential::where('alias', $alias)->first();

            if ($existing && !$force) {
                $resumen[] = [$alias, $user, $config['iban'] ?? '', 'saltado (ya existe)'];
                $saltados++;
                continue;
            }

            $data = [
                'alias' => $alias,
                'label' => $config['label'] ?? ucfirst($alias),
                'user' => $user,
                'password' => $password,
                'iban' => $config['iban'] ?? null,
                'bank_id' => isset($config['bank_id']) ? (int) $config['bank_id'] : null,
                'enabled' => true,
            ];

            if ($existing) {
                $existing->update($data);
                $resumen[] = [$alias, $user, $data['iban'] ?? '', 'actualizado (--force)'];
                $actualizados++;
            } else {
                BankinterCredential::create($data);
                $resumen[] = [$alias, $user, $data['iban'] ?? '', 'creado'];
                $creados++;
            }
        }

        $this->table(['Alias', 'Usuario', 'IBAN', 'Estado'], $resumen);

        $this->newLine();
        $this->info("Creados:      {$creados}");
        $this->info("Actualizados: {$actualizados}");
        $this->info("Saltados:     {$saltados}");
        $this->info("Invalidos:    {$invalidos}");

        return self::SUCCESS;
    }
}
