<script setup>
import { money } from "@/utils/money";

const props = defineProps({
    form: Object
});

const addRow = () => {
    props.form.items.push({
        title: "",
        category: "",
        quantity: 1,
        cost_price: 0,
        sell_price: 0,
        cost_total: 0,
        sell_total: 0,
        profit: 0,
        remarks: ""
    });
};

const removeRow = (index) => {
    if (props.form.items.length === 1) return;

    props.form.items.splice(index, 1);
};

// A live preview only. EstimateService::buildLine recomputes all three from
// quantity and the two prices and ignores whatever is posted, so these values
// never decide anything — they just save the user a round trip to see the line
// total while typing.
const calculateRow = (item) => {
    const quantity = Number(item.quantity || 0);
    const costPrice = Number(item.cost_price || 0);
    const sellPrice = Number(item.sell_price || 0);

    item.cost_total = quantity * costPrice;
    item.sell_total = quantity * sellPrice;
    item.profit = item.sell_total - item.cost_total;
};
</script>

<template>

<div class="estimate-items">

<h3>Quoted Items</h3>

<p class="hint">
    Each line calculates cost, sell, and profit from the quantity and pricing entered here.
</p>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>Title</th>

<th>Category</th>

<th width="110">Qty</th>

<th width="150">Cost Price</th>

<th width="150">Sell Price</th>

<th width="140" class="right">Sell Total</th>

<th width="60"></th>

</tr>

</thead>

<tbody>

<tr
v-for="(item,index) in form.items"
:key="index"
>

<td>

<input
v-model="item.title"
placeholder="Freight"
/>

</td>

<td>

<select v-model="item.category">

<option value="">Category</option>

<option>Labor</option>

<option>Transport</option>

<option>Vehicle</option>

<option>Fuel</option>

<option>Machinery</option>

<option>Agent</option>

<option>Other</option>

</select>

</td>

<td>

<input
type="number"
min="1"
v-model="item.quantity"
@input="calculateRow(item)"
/>

</td>

<td>

<input
type="number"
min="0"
v-model="item.cost_price"
@input="calculateRow(item)"
/>

</td>

<td>

<input
type="number"
min="0"
v-model="item.sell_price"
@input="calculateRow(item)"
/>

</td>

<td class="right">

{{ money(item.sell_total) }}

</td>

<td class="right">

<button
type="button"
class="btn-danger btn-sm"
@click="removeRow(index)"
>

&times;

</button>

</td>

</tr>

</tbody>

</table>

</div>

<div class="actions">

<button
type="button"
class="btn-light btn-sm"
@click="addRow"
>

+ Add Item

</button>

</div>

</div>

</template>
