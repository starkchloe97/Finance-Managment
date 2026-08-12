<script setup>
import { computed } from "vue";

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

const subtotal = computed(() => {

    return props.form.items.reduce((sum,item)=>{

        return sum + Number(item.total);

    },0);

});
</script>

<template>

<div class="estimate-items">

<h3>Cost Breakdown</h3>

<table>

<thead>

<tr>

<th>Title</th>

<th>Category</th>

<th width="100">Qty</th>

<th width="150">Rate</th>

<th width="150">Total</th>

<th width="80"></th>

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
placeholder="Labor"
/>

</td>

<td>

<select
v-model="item.category"
>

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

<td>

{{ item.total.toLocaleString() }}

</td>

<td>

<button
type="button"
@click="removeRow(index)"
>

X

</button>

</td>

</tr>

</tbody>

</table>

<button
type="button"
@click="addRow"
>

+ Add Item

</button>

<div class="subtotal">

<h3>

Subtotal

{{ subtotal.toLocaleString() }}

</h3>

</div>

</div>

</template>