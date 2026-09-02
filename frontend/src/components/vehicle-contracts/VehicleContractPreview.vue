<script setup>
import { computed } from 'vue'

const props = defineProps({
  contract: {
    type: Object,
    required: true,
  },
})

const formatDate = (value) => {
  if (!value) return '________________'

  const date = new Date(`${value}T00:00:00`)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const vehicleDescription = computed(() => {
  return [
    props.contract.vehicle_make,
    props.contract.vehicle_model,
    props.contract.vehicle_type
      ? `– ${props.contract.vehicle_type}`
      : '',
  ]
    .filter(Boolean)
    .join(' ')
})

const serviceDescription = computed(() => {
  const driver =
    props.contract.service_type === 'with_driver'
      ? 'with Driver'
      : 'without Driver'

  const fuel =
    props.contract.fuel_included
      ? 'with Fuel'
      : 'without Fuel'

  return `Vehicle Rental Agreement ${driver} and ${fuel}`
})
</script>

<template>
  <article class="contract-document">

    <header class="contract-header">
      <h1>RENTAL VEHICLE AGREEMENT</h1>

      <p>
        This Rental Vehicle Agreement ("Agreement") is made and
        entered into on this
        <strong>
          {{ formatDate(contract.agreement_date) }}
        </strong>.
      </p>
    </header>

    <section>
      <h2>BETWEEN</h2>

      <p>
        <strong>
          {{ contract.vendor_name || 'Vendor Name' }}
        </strong>
      </p>

      <p>
        Office Address:
        {{ contract.vendor_address || 'Vendor Address' }}
      </p>

      <p>
        Hereinafter referred to as the
        <strong>"Vendor"</strong>
      </p>
    </section>

    <section>
      <h2>AND</h2>

      <p>
        <strong>
          {{ contract.customer_name || 'Customer / User' }}
        </strong>
      </p>

      <p>
        Head Office:
        {{ contract.customer_address || 'Customer Address' }}
      </p>

      <p>
        Hereinafter referred to as the
        <strong>"Customer / User"</strong>
      </p>

      <p>
        The Vendor and the Customer shall hereinafter collectively
        be referred to as the <strong>"Parties."</strong>
      </p>
    </section>

    <section>
      <h2>1. NATURE OF AGREEMENT</h2>

      <p>
        This is a
        <strong>{{ serviceDescription }}</strong>.
      </p>

      <p>
        The vehicles shall be used strictly for the Customer's
        commercial operations and in accordance with the terms
        and conditions of this Agreement.
      </p>
    </section>

    <section>
      <h2>2. VEHICLE RENTAL DETAILS</h2>

      <p>
        The Vendor agrees to provide the following vehicles to
        the Customer on a rental basis:
      </p>

      <ul>
        <li>
          <strong>Total Vehicles:</strong>
          {{ contract.total_vehicles }} Units
        </li>

        <li>
          <strong>Make / Model:</strong>
          {{ contract.vehicle_make }}
          {{ contract.vehicle_model }}
        </li>

        <li>
          <strong>Model Year:</strong>
          {{ contract.vehicle_model_year }}
        </li>

        <li>
          <strong>Vehicle Type:</strong>
          {{ contract.vehicle_type }}
        </li>
      </ul>

      <h3>Rental Breakdown</h3>

      <table class="contract-table">
        <thead>
          <tr>
            <th>Description</th>
            <th>Quantity</th>
            <th>Monthly Rental Per Vehicle</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>
              {{ vehicleDescription }}
            </td>

            <td>
              {{ contract.total_vehicles }} Units
            </td>

            <td>
              PKR
              {{ formatMoney(
                contract.monthly_rental_per_vehicle
              ) }}
            </td>
          </tr>
        </tbody>
      </table>

      <p>
        <strong>
          Total Monthly Rental:
          PKR {{ formatMoney(contract.total_monthly_rental) }}
          excluding applicable taxes and fuel.
        </strong>
      </p>
    </section>

    <section>
      <h2>3. SCOPE OF RENTAL</h2>

      <p>The vehicles shall be provided:</p>

      <ul>
        <li>
          {{
            contract.service_type === 'with_driver'
              ? 'With driver'
              : 'Without driver'
          }}
        </li>

        <li>
          {{
            contract.routine_maintenance_included
              ? 'With routine maintenance'
              : 'Without routine maintenance'
          }}
        </li>

        <li>
          {{
            contract.fuel_included
              ? 'With fuel'
              : 'Without fuel'
          }}
        </li>
      </ul>

      <p v-if="!contract.fuel_included">
        The Customer shall be responsible for fuel consumption
        and fuel expenses.
      </p>
    </section>

    <section>
      <h2>4. RENTAL RATE AND DUTY HOURS</h2>

      <p>
        The agreed rental rate is
        <strong>
          PKR
          {{ formatMoney(
            contract.monthly_rental_per_vehicle
          ) }}
          per vehicle per month
        </strong>,
        excluding fuel and applicable taxes.
      </p>

      <p>
        The rental includes driver services for:
      </p>

      <ul>
        <li>
          <strong>
            {{ contract.duty_hours_per_day }} hours per day
          </strong>
        </li>

        <li>
          <strong>
            {{ contract.duty_days_per_week }} days per week
          </strong>
        </li>
      </ul>

      <p>
        In case of duty on a public holiday or weekly off day:
      </p>

      <ul>
        <li>
          DLI shall charge
          <strong>
            PKR
            {{ formatMoney(contract.public_holiday_rate) }}
            for up to {{ contract.duty_hours_per_day }} hours.
          </strong>
        </li>

        <li>
          Duty exceeding {{ contract.duty_hours_per_day }} hours
          shall be charged as overtime at
          <strong>
            PKR
            {{ formatMoney(contract.overtime_rate) }}
            per additional hour.
          </strong>
        </li>
      </ul>
    </section>

    <section>
      <h2>5. ROAD TAX AND INSURANCE</h2>

      <p>
        Road tax and comprehensive insurance coverage for the
        vehicles shall be included in the agreed monthly rental.
      </p>

      <p>
        All vehicles shall be equipped with a tracking system.
      </p>
    </section>

    <section>
      <h2>6. PAYMENT TERMS</h2>

      <p>
        DLI shall issue monthly invoices to the Customer.
      </p>

      <p>
        The Customer shall make payment within
        <strong>
          {{ contract.payment_terms || '10-15 days' }}
        </strong>
        from the date of invoice issuance.
      </p>

      <p>
        Any applicable government taxes shall be charged
        separately as per prevailing Government of Pakistan
        regulations.
      </p>
    </section>

    <section>
      <h2>7. ADVANCE PAYMENT</h2>

      <p>
        The Customer shall pay
        <strong>
          {{ contract.advance_months }} month(s) advance rental
        </strong>
        before commencement of the rental services.
      </p>
    </section>

    <section>
      <h2>8. SERVICE AND COMPLAINT REQUESTS</h2>

      <p>
        All service requests, complaints, operational instructions,
        and vehicle-related requests shall be submitted in writing
        through email by an authorized representative of the Customer.
      </p>
    </section>

    <section>
      <h2>9. ROUTINE MAINTENANCE</h2>

      <p>
        Routine maintenance of the vehicles, including:
      </p>

      <ul>
        <li>Engine oil</li>
        <li>Oil filters</li>
        <li>Air filters</li>
        <li>Regular servicing</li>
        <li>Normal mechanical maintenance</li>
      </ul>

      <p>
        shall be the responsibility of DLI.
      </p>
    </section>

    <section>
      <h2>10. WORKSHOP / REPAIR PERIOD</h2>

      <p>
        In case any vehicle is required to be taken to the workshop
        for repair or maintenance, the full monthly rental shall
        remain payable.
      </p>

      <p>
        No rental deduction, adjustment, or waiver shall be
        applicable during the workshop period.
      </p>

      <p>
        DLI shall make reasonable efforts to complete repairs and
        return the vehicle to operational service at the earliest
        possible time.
      </p>
    </section>

    <section>
      <h2>11. THEFT, SNATCHING OR TOTAL LOSS</h2>

      <ol>
        <li>
          The Customer shall remain responsible for any applicable
          depreciation or other amount not covered by the insurance claim.
        </li>

        <li>
          Monthly rental shall continue until final settlement
          of the insurance claim.
        </li>

        <li>
          The estimated insurance claim settlement period shall
          be approximately
          <strong>
            {{ contract.insurance_claim_period_days }} days
          </strong>,
          subject to the insurance company's procedures and requirements.
        </li>

        <li>
          Any amount payable by the Customer under the insurance
          policy or due to its negligence shall be borne by the Customer.
        </li>
      </ol>
    </section>

    <section>
      <h2>12. MILEAGE LIMIT</h2>

      <p>
        The monthly mileage limit shall be:
      </p>

      <p>
        <strong>
          {{ contract.monthly_mileage_limit }}
          KM per vehicle per month.
        </strong>
      </p>

      <p>
        Any mileage exceeding the monthly limit shall be charged at:
      </p>

      <p>
        <strong>
          PKR {{ formatMoney(contract.excess_mileage_rate) }}
          per additional KM.
        </strong>
      </p>

      <p>
        The excess mileage shall be calculated based on the
        vehicle's monthly odometer/tracking records.
      </p>
    </section>

    <section>
      <h2>13. REFRIGERATION UNIT</h2>

      <p>
        The installation, operation, electricity/charging arrangements,
        and maintenance of the plug-in refrigerated container/refrigeration
        unit shall be the sole responsibility of the Customer, unless
        otherwise agreed in writing.
      </p>

      <p>
        Any damage to the refrigeration unit caused by misuse,
        negligence, improper operation, or unauthorized modification
        shall be borne by the Customer.
      </p>
    </section>

    <section>
      <h2>14. EARLY TERMINATION</h2>

      <p>
        If the Customer returns any vehicle before expiry of the
        Agreement without the written consent of DLI, the Customer
        shall pay an
        <strong>
          early termination charge equivalent to
          {{ contract.early_termination_months }}
          months' rental per vehicle.
        </strong>
      </p>

      <p>
        Any outstanding amount, excess mileage charges, damages,
        taxes, or other payable amounts shall also be settled by
        the Customer.
      </p>
    </section>

    <section>
      <h2>15. AGREEMENT VALIDITY</h2>

      <p>
        This Agreement shall remain valid for a period of
        <strong>
          {{ contract.duration_months }} months
        </strong>,
        ending on
        <strong>{{ formatDate(contract.end_date) }}</strong>,
        unless terminated earlier in accordance with the terms
        of this Agreement.
      </p>

      <p>
        Any extension or renewal shall be mutually agreed upon
        in writing by both Parties.
      </p>
    </section>

    <section>
      <h2>16. FUEL</h2>

      <p>
        Fuel is
        <strong>
          {{ contract.fuel_included ? 'included' : 'not included' }}
        </strong>
        in the monthly rental.
      </p>

      <p v-if="!contract.fuel_included">
        All fuel expenses required for the operation of the vehicles
        shall be borne by the Customer.
      </p>
    </section>

    <section>
      <h2>17. VEHICLE USE</h2>

      <p>The Customer shall ensure that:</p>

      <ul>
        <li>Vehicles are used only for lawful commercial purposes.</li>
        <li>Vehicles are operated by authorized/licensed drivers.</li>
        <li>No unauthorized modifications are made to the vehicles.</li>
        <li>Vehicles are not used for any illegal activity.</li>
        <li>
          The vehicles are not sub-rented or transferred to any
          third party without prior written approval from DLI.
        </li>
      </ul>
    </section>

    <section>
      <h2>18. DAMAGE AND NEGLIGENCE</h2>

      <p>
        Any damage resulting from negligence, misuse, unauthorized
        use, overloading, improper operation, or violation of traffic
        laws by the Customer or its representatives shall be the
        responsibility of the Customer.
      </p>

      <p>
        Normal wear and tear shall not be considered Customer damage.
      </p>
    </section>

    <section>
      <h2>19. TAXES / GST</h2>

      <p>
        Applicable Sales Tax / GST and other government taxes shall
        be charged separately in accordance with the prevailing laws
        and regulations of the Government of Pakistan.
      </p>
    </section>

    <section>
      <h2>20. GOVERNING LAW AND JURISDICTION</h2>

      <p>
        This Agreement shall be governed by and interpreted in
        accordance with the laws of the
        <strong>Islamic Republic of Pakistan</strong>.
      </p>

      <p>
        The courts at <strong>Karachi, Pakistan</strong>, shall
        have exclusive jurisdiction over any dispute arising from
        or relating to this Agreement.
      </p>
    </section>

    <section>
      <h2>21. AMENDMENTS</h2>

      <p>
        Any amendment, modification, addition, or change to this
        Agreement shall only be valid if made in writing and signed
        by authorized representatives of both Parties.
      </p>
    </section>

    <section>
      <h2>22. ENTIRE AGREEMENT</h2>

      <p>
        This Agreement constitutes the complete understanding between
        the Parties concerning the rental of the vehicles and supersedes
        any prior verbal or written understanding relating to the same
        subject matter.
      </p>
    </section>

    <section class="signatures">
      <h1>SIGNATURES</h1>

      <p>
        IN WITNESS WHEREOF, the Parties have signed this Agreement
        on the date mentioned above.
      </p>

      <div class="signature-grid">
        <div>
          <h2>FOR VENDOR</h2>

          <strong>{{ contract.vendor_name }}</strong>

          <p>
            Name:
            {{ contract.vendor_signatory_name || '____________________' }}
          </p>

          <p>
            Designation:
            {{
              contract.vendor_signatory_designation ||
              '____________________'
            }}
          </p>

          <p>
            CNIC:
            {{ contract.vendor_signatory_cnic || '____________________' }}
          </p>

          <p>Signature: ____________________</p>

          <p>
            Date:
            {{
              formatDate(contract.vendor_signature_date)
            }}
          </p>
        </div>

        <div>
          <h2>FOR CUSTOMER</h2>

          <strong>{{ contract.customer_name }}</strong>

          <p>
            Name:
            {{
              contract.customer_signatory_name ||
              '____________________'
            }}
          </p>

          <p>
            Designation:
            {{
              contract.customer_signatory_designation ||
              '____________________'
            }}
          </p>

          <p>
            CNIC:
            {{
              contract.customer_signatory_cnic ||
              '____________________'
            }}
          </p>

          <p>Signature: ____________________</p>

          <p>
            Date:
            {{
              formatDate(contract.customer_signature_date)
            }}
          </p>
        </div>
      </div>

      <div class="witness-grid">
        <div>
          <h2>WITNESS 1</h2>

          <p>
            Name:
            {{ contract.witness_1_name || '____________________' }}
          </p>

          <p>
            CNIC:
            {{ contract.witness_1_cnic || '____________________' }}
          </p>

          <p>Signature: ____________________</p>
        </div>

        <div>
          <h2>WITNESS 2</h2>

          <p>
            Name:
            {{ contract.witness_2_name || '____________________' }}
          </p>

          <p>
            CNIC:
            {{ contract.witness_2_cnic || '____________________' }}
          </p>

          <p>Signature: ____________________</p>
        </div>
      </div>
    </section>

  </article>
</template>

<style scoped>
.contract-document {
  width: 100%;
  max-width: 820px;
  margin: 0 auto;
  padding: 50px 60px;
  background: #fff;
  color: #111827;
  font-family: Arial, Helvetica, sans-serif;
  line-height: 1.65;
  box-sizing: border-box;
}

.contract-header {
  text-align: center;
  margin-bottom: 2rem;
}

.contract-document h1 {
  margin: 0 0 1.5rem;
  font-size: 1.45rem;
  line-height: 1.3;
}

.contract-document h2 {
  margin: 2rem 0 0.75rem;
  font-size: 1rem;
  line-height: 1.4;
}

.contract-document h3 {
  margin: 1.25rem 0 0.75rem;
  font-size: 0.9rem;
}

.contract-document p {
  margin: 0 0 0.9rem;
  font-size: 0.9rem;
}

.contract-document ul,
.contract-document ol {
  margin: 0 0 1rem;
  padding-left: 1.5rem;
}

.contract-document li {
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}

.contract-table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.contract-table th,
.contract-table td {
  padding: 0.6rem;
  border: 1px solid #111827;
  text-align: left;
  vertical-align: top;
  font-size: 0.8rem;
}

.contract-table th {
  font-weight: 700;
}

.signature-grid,
.witness-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 3rem;
  margin-top: 2rem;
}

.signature-grid > div,
.witness-grid > div {
  min-width: 0;
}

.signatures {
  margin-top: 3rem;
}

@media (max-width: 768px) {
  .contract-document {
    padding: 30px 20px;
  }

  .signature-grid,
  .witness-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

@media print {
  .contract-document {
    max-width: none;
    width: 100%;
    margin: 0;
    padding: 0;
  }

  .contract-document h2 {
    break-after: avoid;
  }

  .contract-table {
    break-inside: avoid;
  }

  .signature-grid,
  .witness-grid {
    break-inside: avoid;
  }
}
</style>