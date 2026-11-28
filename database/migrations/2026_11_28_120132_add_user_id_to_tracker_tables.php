<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId !== null) {
            DB::table('companies')->whereNull('user_id')->update(['user_id' => $ownerId]);
            DB::table('job_applications')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        $this->makeCompanyNameUniquePerUser();
        $this->tightenToNonNullable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        $this->makeCompanyNamesGloballyUnique();
    }

    /**
     * Company names are only unique within a user, so two users may track the same company.
     */
    private function makeCompanyNameUniquePerUser(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique('companies_name_unique');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Rolling back restores a global unique on companies.name, which per-user
     * duplicates would violate. Suffix the later copies with their row id so
     * the constraint can be re-added without losing rows.
     */
    private function makeCompanyNamesGloballyUnique(): void
    {
        $duplicateNames = DB::table('companies')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('count(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $allIds = DB::table('companies')
                ->where('name', $name)
                ->orderBy('id')
                ->pluck('id');

            foreach ($allIds->skip(1) as $id) {
                DB::table('companies')->where('id', $id)->update(['name' => $name.' #'.$id]);
            }
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Existing rows are backfilled to the oldest user. If rows remain without an
     * owner (no users exist yet), the column stays nullable so the migration does
     * not fail; the app never writes null and the ownership test asserts this.
     */
    private function tightenToNonNullable(): void
    {
        $hasOrphans = DB::table('companies')->whereNull('user_id')->exists()
            || DB::table('job_applications')->whereNull('user_id')->exists();

        if ($hasOrphans) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
