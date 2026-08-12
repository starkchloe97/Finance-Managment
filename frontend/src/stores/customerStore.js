import { defineStore } from "pinia";
import * as service from "@/services/customerService";

export const useCustomerStore=defineStore("customers",{

    state: () => ({
    customers: [],
    loading: false,
    search: "",
}),

    actions:{

    async fetchCustomers() {

        this.loading = true;

        try {

            const response = await service.getCustomers({
                search: this.search,
            });

            this.customers = response.data.data;

        } finally {

            this.loading = false;

        }
        },  
    async deleteCustomer(id) {

        await service.deleteCustomer(id);

        await this.fetchCustomers();

    },
    async loadOptions() {

    const response = await service.getCustomers();

    this.customers = response.data.data;

}

    }

});