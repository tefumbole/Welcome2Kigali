<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateMembershipModule extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_groups')) {
            Schema::table('customer_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('customer_groups', 'discount_mode')) {
                    $table->string('discount_mode', 20)->default('markup')->after('percentage');
                }
                if (! Schema::hasColumn('customer_groups', 'is_system')) {
                    $table->boolean('is_system')->default(0)->after('is_active');
                }
            });
        }

        if (! Schema::hasTable('membership_plans')) {
            Schema::create('membership_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->unsignedSmallInteger('duration_months');
                $table->decimal('fee', 14, 2)->default(0);
                $table->boolean('is_active')->default(1);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_promotions')) {
            Schema::create('membership_promotions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('type', 40)->default('free_days');
                $table->unsignedInteger('free_days')->default(90);
                $table->boolean('is_enabled')->default(1);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_agreements')) {
            Schema::create('membership_agreements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('version', 40);
                $table->string('title');
                $table->longText('body');
                $table->boolean('is_current')->default(0);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_settings')) {
            Schema::create('membership_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('key', 80)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_applications')) {
            Schema::create('membership_applications', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 40)->unique();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedInteger('agreement_id')->nullable();
                $table->unsignedInteger('promotion_id')->nullable();
                $table->unsignedInteger('customer_id')->nullable();
                $table->unsignedInteger('beyond_user_id')->nullable();
                $table->string('full_name');
                $table->string('email');
                $table->string('phone');
                $table->string('company_name')->nullable();
                $table->string('id_type', 40)->nullable();
                $table->string('status', 40)->default('PENDING');
                $table->text('admin_note')->nullable();
                $table->longText('signature_image')->nullable();
                $table->dateTime('signed_at')->nullable();
                $table->string('signed_agreement_version', 40)->nullable();
                $table->ipAddress('submitted_ip')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->increments('id');
                $table->string('number', 40)->unique();
                $table->unsignedInteger('customer_id')->index();
                $table->unsignedInteger('application_id')->nullable();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedInteger('promotion_id')->nullable();
                $table->unsignedInteger('previous_customer_group_id')->nullable();
                $table->string('status', 40)->default('PENDING');
                $table->boolean('is_promotional')->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->string('qr_token', 64)->unique();
                $table->string('renew_token', 64)->nullable()->unique();
                $table->string('confirmation_pdf')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_payments')) {
            Schema::create('membership_payments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('membership_id')->index();
                $table->unsignedInteger('plan_id')->nullable();
                $table->unsignedInteger('sale_id')->nullable();
                $table->unsignedInteger('payment_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('method', 40)->nullable();
                $table->string('status', 40)->default('pending');
                $table->string('reference')->nullable();
                $table->string('campay_reference')->nullable();
                $table->boolean('is_renewal')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_product_benefits')) {
            Schema::create('membership_product_benefits', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('product_id')->index();
                $table->string('kind', 20)->default('free');
                $table->decimal('member_price', 14, 2)->nullable();
                $table->unsignedInteger('qty')->default(1);
                $table->string('frequency', 30)->default('unlimited');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_benefit_redemptions')) {
            Schema::create('membership_benefit_redemptions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('membership_id')->index();
                $table->unsignedInteger('customer_id')->index();
                $table->unsignedInteger('product_id')->index();
                $table->unsignedInteger('sale_id')->nullable();
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('value', 14, 2)->default(0);
                $table->dateTime('redeemed_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_documents')) {
            Schema::create('membership_documents', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('application_id')->nullable()->index();
                $table->unsignedInteger('membership_id')->nullable()->index();
                $table->string('doc_type', 40);
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_notifications')) {
            Schema::create('membership_notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('membership_id')->index();
                $table->string('kind', 40);
                $table->string('channel', 20)->default('whatsapp');
                $table->string('status', 20)->default('sent');
                $table->text('payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('membership_audit_logs')) {
            Schema::create('membership_audit_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('membership_id')->nullable()->index();
                $table->unsignedInteger('application_id')->nullable()->index();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('action', 80);
                $table->text('meta')->nullable();
                $table->timestamps();
            });
        }

        $this->seedPermissions();
        $this->seedDefaults();
    }

    private function seedPermissions()
    {
        $names = [
            'membership_module',
            'memberships.view',
            'memberships.approve',
            'memberships.reject',
            'memberships.suspend',
            'memberships.renew',
            'memberships.settings',
            'memberships.promotions',
            'memberships.benefits',
            'memberships.payments',
            'memberships.documents',
            'memberships.reports',
        ];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $roles = Role::whereIn('name', ['Admin', 'admin', 'Owner', 'Super Admin'])->get();
        if ($roles->isEmpty()) {
            $roles = Role::whereIn('id', [1, 2])->get();
        }
        foreach ($roles as $role) {
            foreach ($names as $name) {
                try {
                    $role->givePermissionTo($name);
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function seedDefaults()
    {
        $now = now();
        $groupId = null;
        if (Schema::hasTable('customer_groups')) {
            $existing = DB::table('customer_groups')->where('name', 'Welcome to Kigali Members')->first();
            if ($existing) {
                $groupId = $existing->id;
                DB::table('customer_groups')->where('id', $groupId)->update([
                    'percentage' => '10',
                    'discount_mode' => 'discount',
                    'is_system' => 1,
                    'is_active' => 1,
                    'updated_at' => $now,
                ]);
            } else {
                $groupId = DB::table('customer_groups')->insertGetId([
                    'name' => 'Welcome to Kigali Members',
                    'percentage' => '10',
                    'discount_mode' => 'discount',
                    'is_active' => 1,
                    'is_system' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $plans = [
            ['monthly', 'Monthly', 1, 15000, 1],
            ['quarterly', 'Quarterly', 3, 40000, 2],
            ['six_months', 'Six Months', 6, 75000, 3],
            ['annual', 'Annual', 12, 140000, 4],
        ];
        foreach ($plans as $plan) {
            $row = DB::table('membership_plans')->where('code', $plan[0])->first();
            if (! $row) {
                DB::table('membership_plans')->insert([
                    'code' => $plan[0],
                    'name' => $plan[1],
                    'duration_months' => $plan[2],
                    'fee' => $plan[3],
                    'is_active' => 1,
                    'sort_order' => $plan[4],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! DB::table('membership_promotions')->count()) {
            DB::table('membership_promotions')->insert([
                'name' => 'FREE membership for 90 days',
                'type' => 'free_days',
                'free_days' => 90,
                'is_enabled' => 1,
                'starts_at' => $now,
                'ends_at' => null,
                'description' => 'Approved applicants pay zero registration fee and receive 90 days of active membership.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('membership_agreements')->where('is_current', 1)->first()) {
            DB::table('membership_agreements')->insert([
                'version' => '1.0',
                'title' => 'Welcome 2 Kigali Expats Club Membership Agreement',
                'body' => $this->defaultAgreement(),
                'is_current' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $settings = [
            'member_group_id' => (string) $groupId,
            'member_discount_percent' => '10',
            'discount_policy' => 'best',
            'id_doc_types' => json_encode(['national_id', 'passport']),
            'remind_30' => '1',
            'remind_7' => '1',
            'remind_1' => '1',
            'remind_on_expiry' => '1',
            'remind_after_expiry' => '0',
        ];
        foreach ($settings as $key => $value) {
            if (! DB::table('membership_settings')->where('key', $key)->first()) {
                DB::table('membership_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function defaultAgreement()
    {
        return "Welcome 2 Kigali Expats Club Membership Terms\n\n"
            ."1. Membership is personal and non-transferable.\n"
            ."2. Active members receive the published member discount and any free member benefits while membership is ACTIVE.\n"
            ."3. Fees are set by the Club and may change; you pay the fee in force at the time of registration or renewal.\n"
            ."4. Promotional memberships (including free trial periods) end on the stated expiry date unless you renew on a paid plan.\n"
            ."5. When membership expires or is suspended, member discounts and free benefits stop. You remain a customer at standard prices.\n"
            ."6. The Club may suspend membership for misuse of benefits or unpaid fees.\n"
            ."7. You agree that the Club may contact you on WhatsApp about your membership, renewals, and club notices.\n"
            ."8. Identification documents you upload are used only to verify membership.\n";
    }

    public function down()
    {
        Schema::dropIfExists('membership_audit_logs');
        Schema::dropIfExists('membership_notifications');
        Schema::dropIfExists('membership_documents');
        Schema::dropIfExists('membership_benefit_redemptions');
        Schema::dropIfExists('membership_product_benefits');
        Schema::dropIfExists('membership_payments');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_applications');
        Schema::dropIfExists('membership_settings');
        Schema::dropIfExists('membership_agreements');
        Schema::dropIfExists('membership_promotions');
        Schema::dropIfExists('membership_plans');
    }
}
