    <?php

    use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_jobs', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();

            $table->foreignId('estimate_id')->constrained();

            $table->foreignId('customer_id')->constrained();

            $table->date('job_date');

            $table->enum('status', [
                'draft',
                'ready',
                'budgeted',
                'funded',
                'in_progress',
                'completed',
                'closed',
                'cancelled',
            ])->default('draft');

            // Copied from the estimate when the job is created. Both are fixed
            // at that point, so base_profit is the profit we signed up for.
            $table->decimal('sell_price', 15, 2)->default(0);

            $table->decimal('cost_price', 15, 2)->default(0);

            $table->decimal('base_profit', 15, 2)->default(0);

            // Unexpected costs found during the job. Every one of these eats
            // into the profit above.
            $table->decimal('extra_costs', 15, 2)->default(0);

            $table->decimal('final_profit', 15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_jobs');
    }
};
