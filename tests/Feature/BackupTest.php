<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupRunner;
use App\Support\DatabaseDumper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function fakeDumper(bool $fail = false): DatabaseDumper
{
    return new class($fail) extends DatabaseDumper
    {
        public function __construct(private bool $fail)
        {
        }

        public function filename(): string
        {
            return 'database.sql';
        }

        public function dump(string $to): void
        {
            if ($this->fail) {
                throw new RuntimeException('disk is full');
            }
            file_put_contents($to, "-- fake dump\nINSERT INTO things VALUES (1);\n");
        }
    };
}

beforeEach(function () {
    Storage::fake('local');
    $this->backupDir = sys_get_temp_dir().'/ls_backup_'.uniqid();
    config(['security.backup_path' => $this->backupDir, 'security.backup_keep' => 14]);
});

afterEach(fn () => File::deleteDirectory($this->backupDir));

it('packs the database and every uploaded photo and PDF into one verified archive, and nothing else', function () {
    Storage::disk('local')->put('media/2026/10/abc/original.jpg', 'photo-bytes');
    Storage::disk('local')->put('issues/edition.pdf', '%PDF-1.4 pdf-bytes');
    Storage::disk('local')->put('livewire-tmp/other.txt', 'not part of the backup');

    $path = (new BackupRunner(fakeDumper()))->create();

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    expect($zip->locateName('database.sql'))->not->toBeFalse()
        ->and($zip->locateName('MANIFEST.txt'))->not->toBeFalse()
        ->and($zip->locateName('files/media/2026/10/abc/original.jpg'))->not->toBeFalse()
        ->and($zip->locateName('files/issues/edition.pdf'))->not->toBeFalse()
        ->and($zip->locateName('files/livewire-tmp/other.txt'))->toBeFalse();
    expect($zip->getFromName('database.sql'))->toContain('INSERT INTO things');
    $zip->close();
});

it('leaves no broken archive behind when the database export fails', function () {
    expect(fn () => (new BackupRunner(fakeDumper(true)))->create())->toThrow(RuntimeException::class, 'disk is full');

    expect((new BackupRunner(fakeDumper()))->list())->toBe([]);
});

it('never overwrites an earlier backup, even one made in the same second, and a failure never deletes a good one', function () {
    $runner = new BackupRunner(fakeDumper());
    $first = $runner->create();
    $second = $runner->create();

    expect($second)->not->toBe($first)->and(count($runner->list()))->toBe(2);

    expect(fn () => (new BackupRunner(fakeDumper(true)))->create())->toThrow(RuntimeException::class);
    expect(count($runner->list()))->toBe(2)->and(file_exists($first))->toBeTrue();
});

it('keeps only the newest archives', function () {
    @mkdir($this->backupDir, 0750, true);
    foreach (['20260101-000000' => 1000, '20260102-000000' => 2000, '20260103-000000' => 3000, '20260104-000000' => 4000] as $stamp => $time) {
        touch("{$this->backupDir}/backup-{$stamp}.zip", $time);
    }

    $removed = (new BackupRunner(fakeDumper()))->prune(2);

    expect($removed)->toBe(2);
    expect(array_column((new BackupRunner(fakeDumper()))->list(), 'name'))->toBe(['backup-20260104-000000.zip', 'backup-20260103-000000.zip']);
});

it('reports clearly when the database cannot be backed up as a file', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'x');

    expect(fn () => DatabaseDumper::forDefault()->dump($tmp))->toThrow(RuntimeException::class, 'not a file'); // the in-memory test database

    @unlink($tmp);
});

it('lets only admins view and run backups, and records them', function () {
    $this->app->bind(DatabaseDumper::class, fn () => fakeDumper());
    $admin = User::factory()->admin()->create();

    $this->get('/admin/backups')->assertRedirect('/login');
    $this->actingAs(User::factory()->editor()->create())->get('/admin/backups')->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->post('/admin/backups')->assertForbidden();

    $this->actingAs($admin)->post('/admin/backups')->assertRedirect()->assertSessionHas('success');
    $this->actingAs($admin)->get('/admin/backups')->assertInertia(fn (Assert $p) => $p->component('admin/backups')->has('backups', 1));
    expect(AuditLog::where('action', 'backup.created')->count())->toBe(1);
});

it('shows a clear error and records the failure when a backup cannot be made', function () {
    $this->app->bind(DatabaseDumper::class, fn () => fakeDumper(true));
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/backups')->assertSessionHasErrors('backup');

    expect(AuditLog::where('action', 'backup.failed')->count())->toBe(1);
    $this->actingAs($admin)->get('/admin/backups')->assertInertia(fn (Assert $p) => $p->has('backups', 0));
});

it('runs from the command line for the nightly schedule', function () {
    $this->app->bind(DatabaseDumper::class, fn () => fakeDumper());
    $this->artisan('backup:run')->assertSuccessful();
    expect(AuditLog::where('action', 'backup.created')->count())->toBe(1);

    $this->app->bind(DatabaseDumper::class, fn () => fakeDumper(true));
    $this->artisan('backup:run')->assertFailed();
    expect(AuditLog::where('action', 'backup.failed')->count())->toBe(1);
});
