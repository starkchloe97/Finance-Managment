import { defineStore } from 'pinia'
import { assetService } from '@/services/assetService'

export const useAssetStore = defineStore(
  'assets',
  {
    state: () => ({
      assets: [],
      currentAsset: null,

      pagination: null,

      loading: false,
      detailLoading: false,
      saving: false,
      deleting: false,

      error: null,
    }),

    actions: {
      async fetchAssets(params = {}) {
        this.loading = true
        this.error = null

        try {
          const response =
            await assetService.getAll(params)

          this.assets =
            response.data ?? []

          this.pagination =
            response.meta ?? null

          return response
        } catch (error) {
          this.error =
            error.response?.data?.message ||
            'Unable to load assets.'

          throw error
        } finally {
          this.loading = false
        }
      },

      async fetchAsset(id) {
        this.detailLoading = true
        this.error = null

        try {
          const response =
            await assetService.get(id)

          this.currentAsset =
            response.data

          return response.data
        } catch (error) {
          this.error =
            error.response?.data?.message ||
            'Unable to load asset.'

          throw error
        } finally {
          this.detailLoading = false
        }
      },

      async createAsset(payload) {
        this.saving = true
        this.error = null

        try {
          const response =
            await assetService.create(payload)

          return response.data
        } catch (error) {
          this.error =
            error.response?.data?.message ||
            'Unable to create asset.'

          throw error
        } finally {
          this.saving = false
        }
      },

      async updateAsset(id, payload) {
        this.saving = true
        this.error = null

        try {
          const response =
            await assetService.update(
              id,
              payload
            )

          this.currentAsset =
            response.data

          return response.data
        } catch (error) {
          this.error =
            error.response?.data?.message ||
            'Unable to update asset.'

          throw error
        } finally {
          this.saving = false
        }
      },

      async deleteAsset(id) {
        this.deleting = true
        this.error = null

        try {
          await assetService.delete(id)

          this.assets =
            this.assets.filter(
              asset => asset.id !== id
            )

          if (
            this.currentAsset?.id === id
          ) {
            this.currentAsset = null
          }
        } catch (error) {
          this.error =
            error.response?.data?.message ||
            'Unable to delete asset.'

          throw error
        } finally {
          this.deleting = false
        }
      },
    },
  }
)