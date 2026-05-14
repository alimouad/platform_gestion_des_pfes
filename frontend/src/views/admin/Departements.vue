<script setup>
import { onMounted, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'

const defaultForm = { nom: '', description: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('departements', defaultForm)

onMounted(fetchAll)

const palette = ['bg-[#f0f5e0] text-[#4a7a30]', 'bg-blue-50 text-blue-600', 'bg-purple-50 text-purple-600', 'bg-amber-50 text-amber-600', 'bg-rose-50 text-rose-600', 'bg-cyan-50 text-cyan-600']
function colorFor(id) { return palette[(id - 1) % palette.length] }
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Structure académique</p>
          <h1 class="mt-1 text-2xl font-black">Départements</h1>
          <p class="mt-1 text-sm text-white/60">{{ items.length }} département{{ items.length !== 1 ? 's' : '' }} enregistré{{ items.length !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-5 py-2.5 text-sm font-black text-[#1e4a49] shadow hover:brightness-105 transition">
          <i class="fa-solid fa-plus"></i> Nouveau département
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher un département…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-building-columns text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucun département trouvé</p>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="d in filtered" :key="d.id"
        class="group rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg">

        <div class="mb-5 flex items-start justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl" :class="colorFor(d.id)">
            <i class="fa-solid fa-building-columns"></i>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit(d)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            <button @click="remove(d.id)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-100 transition">
              <i class="fa-solid fa-trash text-xs"></i>
            </button>
          </div>
        </div>

        <h3 class="text-base font-black text-slate-900 leading-snug">{{ d.nom }}</h3>
        <p class="mt-2 text-sm text-slate-400 line-clamp-2">{{ d.description || 'Aucune description' }}</p>

        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-400">
          <i class="fa-solid fa-hashtag text-[10px]"></i>
          <span class="font-bold text-slate-500">ID {{ d.id }}</span>
        </div>
      </article>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl overflow-hidden">
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Département</p>
              <h2 class="mt-0.5 text-base font-black text-white">{{ editing ? 'Modifier' : 'Nouveau département' }}</h2>
            </div>
            <button @click="closeModal" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">{{ error }}</div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Nom du département</label>
              <input v-model="form.nom" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-[#d6e87a] focus:bg-white transition" />
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Description</label>
              <textarea v-model="form.description" rows="3"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="flex-1 rounded-2xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Annuler</button>
              <button type="submit"
                class="flex-1 rounded-2xl bg-[#1e4a49] py-3 text-sm font-black text-white hover:bg-[#163938] transition">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
