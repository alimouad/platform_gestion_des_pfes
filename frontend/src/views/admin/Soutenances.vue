<script setup>
import { onMounted, ref, computed } from 'vue'
import { useCrud } from '@/composables/useCrud'
import api from '@/services/api'

const defaultForm = { projet_id: '', date: '', heure: '', salle: '', statut: 'planifiee', jury: '', note_finale: '' }
const { items, loading, search, filtered, showModal, editing, form, error, fetchAll, save, remove, openCreate, openEdit, closeModal } = useCrud('soutenances', defaultForm)

const projets = ref([])
const filterStatut = ref('all')
const selected = ref(null)

onMounted(async () => {
  await fetchAll()
  try {
    const res = await api.get('/projets')
    projets.value = res.data.data
  } catch {}
})

const statutConfig = {
  planifiee: { label: 'Planifiée', dot: 'bg-blue-500',  badge: 'bg-blue-100 text-blue-700',   bar: 'bg-blue-300',   icon: 'fa-calendar-check', ring: 'ring-blue-200' },
  en_cours:  { label: 'En cours',  dot: 'bg-amber-500', badge: 'bg-amber-100 text-amber-700',  bar: 'bg-amber-300',  icon: 'fa-spinner',        ring: 'ring-amber-200' },
  terminee:  { label: 'Terminée',  dot: 'bg-green-500', badge: 'bg-green-100 text-green-700',  bar: 'bg-[#d6e87a]',  icon: 'fa-graduation-cap', ring: 'ring-green-200' },
  annulee:   { label: 'Annulée',   dot: 'bg-red-400',   badge: 'bg-red-100 text-red-600',      bar: 'bg-red-200',    icon: 'fa-ban',            ring: 'ring-red-200' },
}
function sc(s) { return statutConfig[s] || statutConfig.planifiee }

function fmt(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '—'
}
function fmtDay(d)   { return d ? new Date(d).getDate() : '—' }
function fmtMonth(d) { return d ? new Date(d).toLocaleDateString('fr-FR', { month: 'short' }).toUpperCase() : '' }
function fmtYear(d)  { return d ? new Date(d).getFullYear() : '' }

function juryList(jury) {
  return jury ? jury.split(',').map(j => j.trim()).filter(Boolean) : []
}

const counts = computed(() => ({
  total:     items.value.length,
  planifiee: items.value.filter(s => s.statut === 'planifiee').length,
  terminee:  items.value.filter(s => s.statut === 'terminee').length,
  en_cours:  items.value.filter(s => s.statut === 'en_cours').length,
  annulee:   items.value.filter(s => s.statut === 'annulee').length,
}))

const displayList = computed(() => {
  let list = filtered.value
  if (filterStatut.value !== 'all') list = list.filter(s => s.statut === filterStatut.value)
  return list.slice().sort((a, b) => new Date(a.date) - new Date(b.date))
})

function openDetail(s) { selected.value = s }
function closeDetail()  { selected.value = null }

async function marquerTerminee(s, e) {
  e.stopPropagation()
  try {
    await api.put(`/soutenances/${s.id}`, {
      statut: 'terminee',
      projet_id: s.projet_id ?? s.projet?.id,
      date: s.date,
      heure: s.heure?.slice(0, 5),
      salle: s.salle,
    })
    await fetchAll()
    if (selected.value?.id === s.id) selected.value = { ...selected.value, statut: 'terminee' }
  } catch (err) { alert(err.response?.data?.message || 'Erreur') }
}
</script>

<template>
  <div class="space-y-6">

    <!-- Header banner -->
    <div class="rounded-3xl bg-[#1e4a49] px-8 py-7 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
      <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
      <div class="absolute bottom-0 left-16 h-16 w-16 rounded-full bg-white/5"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Calendrier académique</p>
          <h1 class="mt-1 text-2xl font-black">Soutenances PFE</h1>
          <p class="mt-1 text-sm text-white/60">{{ counts.total }} soutenance{{ counts.total !== 1 ? 's' : '' }} enregistrée{{ counts.total !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate"
          class="flex items-center gap-2 rounded-2xl bg-[#d6e87a] px-6 py-3 text-sm font-black text-[#1e4a49] shadow-lg hover:brightness-105 transition-all active:scale-95">
          <i class="fa-solid fa-plus"></i> Planifier une soutenance
        </button>
      </div>
    </div>

    <!-- KPI strip -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div v-for="(cfg, key) in statutConfig" :key="key"
        @click="filterStatut = filterStatut === key ? 'all' : key"
        class="cursor-pointer rounded-2xl border bg-white/90 px-5 py-4 shadow-sm flex items-center gap-4 transition-all hover:-translate-y-0.5 hover:shadow-md"
        :class="filterStatut === key ? `border-current ring-2 ${cfg.ring}` : 'border-white/70'">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
          :class="key === 'planifiee' ? 'bg-blue-50 text-blue-600' : key === 'en_cours' ? 'bg-amber-50 text-amber-600' : key === 'terminee' ? 'bg-[#f0f5e0] text-[#4a7a30]' : 'bg-red-50 text-red-500'">
          <i :class="`fa-solid ${cfg.icon}`"></i>
        </div>
        <div>
          <p class="text-2xl font-black text-slate-900">{{ counts[key] }}</p>
          <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ cfg.label }}</p>
        </div>
      </div>
    </div>

    <!-- Search + filter row -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex flex-1 min-w-48 items-center gap-3 rounded-2xl border border-white/70 bg-white/90 px-4 py-3 shadow-sm">
        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
        <input v-model="search" placeholder="Rechercher une soutenance…" class="flex-1 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 outline-none" />
      </div>
      <button v-if="filterStatut !== 'all'" @click="filterStatut = 'all'"
        class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-xs font-bold text-slate-500 shadow-sm hover:bg-slate-50 transition">
        <i class="fa-solid fa-xmark"></i> Effacer le filtre
      </button>
    </div>

    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">Chargement…</div>

    <div v-else-if="displayList.length === 0" class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
      <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 mb-4">
        <i class="fa-solid fa-graduation-cap text-2xl text-slate-300"></i>
      </div>
      <p class="text-base font-extrabold text-slate-700">Aucune soutenance trouvée</p>
      <p class="mt-1 text-sm text-slate-400">Planifiez la première soutenance avec le bouton ci-dessus.</p>
    </div>

    <!-- Cards grid -->
    <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="s in displayList" :key="s.id"
        @click="openDetail(s)"
        class="group cursor-pointer rounded-3xl border border-white/70 bg-white/90 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-[#d6e87a] hover:shadow-xl overflow-hidden">

        <!-- Colored top bar -->
        <div class="h-1.5 w-full" :class="sc(s.statut).bar"></div>

        <div class="p-5 flex gap-4">

          <!-- Date calendar block -->
          <div class="flex shrink-0 flex-col items-center justify-center rounded-2xl border border-slate-100 bg-slate-50 w-16 h-16 text-center">
            <span class="text-xl font-black leading-none text-slate-900">{{ fmtDay(s.date) }}</span>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 mt-0.5">{{ fmtMonth(s.date) }}</span>
            <span class="text-[9px] font-bold text-slate-300">{{ fmtYear(s.date) }}</span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <!-- Status badge + actions -->
            <div class="flex items-center justify-between mb-2">
              <span class="flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-black" :class="sc(s.statut).badge">
                <span class="h-1.5 w-1.5 rounded-full" :class="sc(s.statut).dot"></span>
                {{ sc(s.statut).label }}
              </span>
              <div class="flex gap-1.5" @click.stop>
                <button v-if="s.statut !== 'terminee'" @click="marquerTerminee(s, $event)"
                  title="Marquer comme terminée"
                  class="flex h-7 items-center gap-1 rounded-xl bg-green-50 px-2 text-[10px] font-bold text-green-700 hover:bg-green-100 transition">
                  <i class="fa-solid fa-check text-[10px]"></i> Terminée
                </button>
                <button @click="openEdit(s)" class="flex h-7 w-7 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                  <i class="fa-solid fa-pen text-[10px]"></i>
                </button>
                <button @click="remove(s.id)" class="flex h-7 w-7 items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-100 transition">
                  <i class="fa-solid fa-trash text-[10px]"></i>
                </button>
              </div>
            </div>

            <!-- Project title -->
            <h3 class="font-black text-slate-900 leading-snug line-clamp-2 text-sm">{{ s.projet?.titre || '—' }}</h3>

            <!-- Time & room -->
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
              <span class="flex items-center gap-1">
                <i class="fa-regular fa-clock text-slate-400"></i>
                {{ s.heure?.slice(0,5) || '—' }}
              </span>
              <span class="text-slate-200">·</span>
              <span class="flex items-center gap-1">
                <i class="fa-solid fa-door-open text-slate-400"></i>
                Salle {{ s.salle || '—' }}
              </span>
            </div>

            <!-- Jury pills -->
            <div v-if="s.jury" class="mt-2 flex flex-wrap gap-1">
              <span v-for="m in juryList(s.jury)" :key="m"
                class="rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                {{ m }}
              </span>
            </div>
          </div>
        </div>

        <!-- Note finale footer -->
        <div v-if="s.note_finale != null" class="border-t border-[#d6e87a]/30 bg-[#f8faef] px-5 py-3 flex items-center gap-2">
          <i class="fa-solid fa-star text-[#6a8a40] text-xs"></i>
          <span class="text-xs font-black text-[#1e4a49]">Note finale : {{ s.note_finale }}/20</span>
        </div>

        <!-- Hover arrow -->
        <div class="border-t border-slate-100 px-5 py-2.5 flex items-center justify-end">
          <span class="text-[10px] font-bold text-slate-300 group-hover:text-[#1e4a49] transition">
            Voir détail <i class="fa-solid fa-chevron-right text-[8px] ml-1"></i>
          </span>
        </div>
      </article>
    </div>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetail"></div>
        <div class="relative z-10 w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:88vh;overflow-y:auto">

          <!-- Header -->
          <div class="bg-[#1e4a49] px-6 py-5 flex items-start justify-between sticky top-0 z-10">
            <div class="flex items-center gap-4">
              <div class="flex shrink-0 flex-col items-center justify-center rounded-2xl bg-[#d6e87a] w-14 h-14 text-center">
                <span class="text-2xl font-black leading-none text-[#1e4a49]">{{ fmtDay(selected.date) }}</span>
                <span class="text-[8px] font-black uppercase tracking-wider text-[#4a7a30]">{{ fmtMonth(selected.date) }}</span>
              </div>
              <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Détail soutenance</p>
                <h2 class="mt-0.5 text-base font-black text-white leading-snug line-clamp-2">{{ selected.projet?.titre || '—' }}</h2>
              </div>
            </div>
            <button @click="closeDetail" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/60 hover:bg-white/10 transition ml-2">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="p-6 space-y-4">
            <!-- Status -->
            <div class="flex items-center justify-between">
              <span class="flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-black" :class="sc(selected.statut).badge">
                <span class="h-2 w-2 rounded-full" :class="sc(selected.statut).dot"></span>
                {{ sc(selected.statut).label }}
              </span>
              <div class="flex gap-2">
                <button v-if="selected.statut !== 'terminee'" @click="marquerTerminee(selected, $event)"
                  class="flex items-center gap-2 rounded-xl bg-green-50 border border-green-100 px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100 transition">
                  <i class="fa-solid fa-graduation-cap text-[10px]"></i> Marquer terminée
                </button>
                <button @click="openEdit(selected); closeDetail()"
                  class="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                  <i class="fa-solid fa-pen text-[10px]"></i> Modifier
                </button>
              </div>
            </div>

            <!-- Info grid -->
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Date</p>
                <p class="text-sm font-black text-slate-800 capitalize">{{ fmt(selected.date) }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Heure</p>
                <p class="text-sm font-black text-slate-800">{{ selected.heure?.slice(0,5) || '—' }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Salle</p>
                <p class="text-sm font-black text-slate-800">{{ selected.salle || '—' }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Encadrant</p>
                <p class="text-sm font-black text-slate-800">
                  {{ selected.projet?.professeur?.utilisateur ? `${selected.projet.professeur.utilisateur.prenom} ${selected.projet.professeur.utilisateur.nom}` : '—' }}
                </p>
              </div>
            </div>

            <!-- Jury -->
            <div v-if="selected.jury" class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Membres du jury</p>
              <div class="flex flex-wrap gap-2">
                <span v-for="m in juryList(selected.jury)" :key="m"
                  class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
                  <i class="fa-solid fa-user-tie text-[#1e4a49] text-[10px]"></i>
                  {{ m }}
                </span>
              </div>
            </div>

            <!-- Note finale -->
            <div v-if="selected.note_finale != null" class="rounded-2xl border-2 border-[#d6e87a] bg-[#f8faef] p-5 text-center">
              <p class="text-[10px] font-black uppercase tracking-widest text-[#6a8a40] mb-1">Note finale</p>
              <p class="text-5xl font-black text-[#1e4a49]">
                {{ selected.note_finale }}<span class="text-2xl text-slate-400 font-bold">/20</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- CREATE / EDIT MODAL -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden" style="max-height:92vh;overflow-y:auto">
          <div class="bg-[#1e4a49] px-6 py-5 flex items-center justify-between sticky top-0 z-10">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-[#d6e87a]">Soutenance</p>
              <h2 class="mt-0.5 text-base font-black text-white">{{ editing ? 'Modifier la soutenance' : 'Planifier une soutenance' }}</h2>
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
                <input v-model="form.salle" placeholder="ex: A101"
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
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">
                Membres du jury <span class="normal-case font-normal text-slate-300">(séparés par virgule)</span>
              </label>
              <input v-model="form.jury" placeholder="Pr. Martin, Dr. Dupont…"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-[#d6e87a] focus:bg-white transition" />
            </div>

            <div>
              <label class="mb-1.5 block text-[11px] font-black uppercase tracking-widest text-slate-400">
                Note finale <span class="normal-case font-normal text-slate-300">(optionnel · /20)</span>
              </label>
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
