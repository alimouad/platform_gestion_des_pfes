<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { projet_id: '', date: '', heure: '', salle: '', statut: 'planifiee', jury: '', note_finale: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('soutenances', defaultForm)

const projets = ref([])
onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/projets')
    projets.value = res.data.data
  } catch {}
})

const statutConfig = {
  planifiee: { label: 'Planifiée', dot: 'bg-blue-500',  badge: 'bg-blue-100 text-blue-700',   icon: 'fa-calendar-check' },
  en_cours:  { label: 'En cours',  dot: 'bg-amber-500', badge: 'bg-amber-100 text-amber-700',  icon: 'fa-spinner' },
  terminee:  { label: 'Terminée',  dot: 'bg-green-500', badge: 'bg-green-100 text-green-700',  icon: 'fa-graduation-cap' },
  annulee:   { label: 'Annulée',   dot: 'bg-red-400',   badge: 'bg-red-100 text-red-600',      icon: 'fa-ban' },
}
function sc(s) { return statutConfig[s] || statutConfig.planifiee }

function fmt(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '—'
}
function fmtShort(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}

const counts = computed(() => ({
  total:     items.value.length,
  planifiee: items.value.filter(s => s.statut === 'planifiee').length,
  terminee:  items.value.filter(s => s.statut === 'terminee').length,
  en_cours:  items.value.filter(s => s.statut === 'en_cours').length,
}))
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-6 text-white relative overflow-hidden">
      <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-6 right-24 h-24 w-24 rounded-full bg-[#d6e87a]/10"></div>
      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-widest text-[#d6e87a]">Gestion des soutenances</p>
          <h1 class="mt-1 text-2xl font-black">Soutenances</h1>
          <p class="mt-1 text-sm text-white/60">{{ counts.total }} soutenance{{ counts.total !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-5 py-2.5 text-sm font-black text-[#1e4a49] shadow hover:brightness-105 transition">
          <i class="fa-solid fa-plus"></i> Planifier
        </button>
      </div>
    </div>

    <!-- KPI strip -->
    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-2xl border border-white/70 bg-white/90 px-5 py-4 shadow-sm flex items-center gap-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ counts.planifiee }}</p>
          <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Planifiées</p>
        </div>
      </div>
      <div class="rounded-2xl border border-white/70 bg-white/90 px-5 py-4 shadow-sm flex items-center gap-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
          <i class="fa-solid fa-spinner"></i>
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ counts.en_cours }}</p>
          <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">En cours</p>
        </div>
      </div>
      <div class="rounded-2xl border border-white/70 bg-white/90 px-5 py-4 shadow-sm flex items-center gap-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ counts.terminee }}</p>
          <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Terminées</p>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
      <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
      <input v-model="search" placeholder="Rechercher une soutenance…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="filtered.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-20 text-center">
      <i class="fa-solid fa-graduation-cap text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Aucune soutenance trouvée</p>
      <p class="mt-1 text-sm text-slate-400">Planifiez la première soutenance avec le bouton ci-dessus.</p>
    </div>

    <!-- Cards -->
    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="s in filtered" :key="s.id"
        class="group rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#d6e87a] hover:shadow-lg overflow-hidden">

        <!-- Top accent -->
        <div class="h-1.5 w-full"
          :class="s.statut === 'planifiee' ? 'bg-blue-300' : s.statut === 'en_cours' ? 'bg-amber-300' : s.statut === 'terminee' ? 'bg-[#d6e87a]' : 'bg-red-200'"></div>

        <div class="p-5">
          <!-- Status + actions -->
          <div class="mb-4 flex items-center justify-between">
            <span class="flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-black" :class="sc(s.statut).badge">
              <span class="h-1.5 w-1.5 rounded-full" :class="sc(s.statut).dot"></span>
              {{ sc(s.statut).label }}
            </span>
            <div class="flex gap-2">
              <button @click="openEdit(s)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i class="fa-solid fa-pen text-xs"></i>
              </button>
              <button @click="remove(s.id)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-100 transition">
                <i class="fa-solid fa-trash text-xs"></i>
              </button>
            </div>
          </div>

          <!-- Project title -->
          <h3 class="font-black text-slate-900 leading-snug line-clamp-2">{{ s.projet?.titre || '—' }}</h3>

          <!-- Meta -->
          <div class="mt-4 space-y-2">
            <div class="flex items-center gap-3 text-xs text-slate-500">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 shrink-0">
                <i class="fa-regular fa-calendar text-[10px]"></i>
              </div>
              <span class="font-semibold capitalize">{{ fmt(s.date) }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-500">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 shrink-0">
                <i class="fa-regular fa-clock text-[10px]"></i>
              </div>
              <span class="font-semibold">{{ s.heure?.slice(0,5) || '—' }}</span>
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 shrink-0 ml-2">
                <i class="fa-solid fa-door-open text-[10px]"></i>
              </div>
              <span class="font-semibold">Salle {{ s.salle || '—' }}</span>
            </div>
            <div v-if="s.jury" class="flex items-start gap-3 text-xs text-slate-500">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-400 shrink-0 mt-0.5">
                <i class="fa-solid fa-user-tie text-[10px]"></i>
              </div>
              <span class="font-semibold leading-relaxed">{{ s.jury }}</span>
            </div>
          </div>

          <!-- Note finale -->
          <div v-if="s.note_finale != null" class="mt-4 flex items-center gap-3 rounded-2xl bg-[#f0f5e0] px-4 py-3">
            <i class="fa-solid fa-star text-[#6a8a40] text-sm"></i>
            <span class="text-sm font-black text-[#1e4a49]">Note : {{ s.note_finale }}/20</span>
          </div>
        </div>
      </article>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:90vh;overflow-y:auto">
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between sticky top-0 z-10">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Soutenance</p>
              <h2 class="mt-0.5 text-base font-black text-white">{{ editing ? 'Modifier' : 'Planifier une soutenance' }}</h2>
            </div>
            <button @click="closeModal" class="flex h-8 w-8 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <form @submit.prevent="save" class="space-y-4 p-6">
            <div v-if="error" class="rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">{{ error }}</div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Projet</label>
              <select v-model="form.projet_id" required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-[#d6e87a] focus:bg-white transition">
                <option value="">— Sélectionner un projet —</option>
                <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.titre }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Date</label>
                <input v-model="form.date" type="date" required
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
              </div>
              <div>
                <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Heure</label>
                <input v-model="form.heure" type="time" required
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Salle</label>
                <input v-model="form.salle" required placeholder="ex: A101"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
              </div>
              <div>
                <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Statut</label>
                <select v-model="form.statut"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none focus:border-[#d6e87a] focus:bg-white transition">
                  <option v-for="(cfg, key) in statutConfig" :key="key" :value="key">{{ cfg.label }}</option>
                </select>
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Membres du jury <span class="normal-case text-slate-300">(séparés par virgule)</span></label>
              <input v-model="form.jury" placeholder="Pr. Martin, Dr. Dupont…"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">Note finale <span class="normal-case text-slate-300">(optionnel, /20)</span></label>
              <input v-model="form.note_finale" type="number" min="0" max="20" step="0.25" placeholder="—"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
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
