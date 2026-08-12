<script setup>
const props = defineProps({
    form: Object
});

const addRow = () => {

    props.form.items.push({

        title: "",

        category: "",

        quantity: 1,

        unit_price: 0,

        total: 0,

        notes: ""

    });

};

const removeRow = (index) => {

    if (props.form.items.length === 1) return;

    props.form.items.splice(index,1);

};

const calculateRow = (item) => {

    item.total =
        Number(item.quantity) *
        Number(item.unit_price);

};

const money = (value) => Number(value || 0).toLocaleString();
</script>

<template>

<div class="estimate-items">

<h3>Quoted Items</h3>

<p class="hint">
    What the customer pays for each item. Company cost is entered later, in the job budget.
</p>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>Title</th>

<th>Category</th>

<th width="110">Qty</th>

<th width="150">Unit Price</th>

<th width="140" class="right">Amount</th>

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
v-model="item.unit_price"
@input="calculateRow(item)"
/>

</td>

<td class="right">

{{ money(item.total) }}

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
