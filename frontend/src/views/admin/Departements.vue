<script setup>
import { onMounted } from 'vue'
import { useCrud } from '@/composables/useCrud'

const defaultForm = { nom: '', description: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('departements', defaultForm)

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Départements</h1>
        <p class="text-sm text-slate-400">Gestion des départements académiques</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">
        <i class="fa-solid fa-plus"></i> Nouveau département
      </button>
    </div>

    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un département…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>
    <div v-else-if="filtered.length === 0" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Aucun département trouvé</div>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="d in filtered" :key="d.id"
        class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm hover:border-[#d6e87a] transition">
        <div class="mb-4 flex items-start justify-between">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#f0f5e0] text-[#6a8a40]">
            <i class="fa-solid fa-building-columns text-lg"></i>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit(d)" class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button @click="remove(d.id)" class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-100 transition">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
        <h3 class="text-base font-extrabold text-slate-900">{{ d.nom }}</h3>
        <p class="mt-1 text-sm text-slate-400">{{ d.description || 'Aucune description' }}</p>
      </article>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-[2rem] bg-white shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-extrabold text-slate-900">{{ editing ? 'Modifier' : 'Nouveau' }} département</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Nom du département</label>
              <input v-model="form.nom" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a]" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-600">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-[#d6e87a] resize-none"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit" class="flex-1 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white hover:bg-slate-700 transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
