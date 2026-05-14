<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const postulations = ref([])
const soutenances  = ref([])
const loading = ref(false)

async function fetchAll() {
  loading.value = true
  try {
    const [po, so] = await Promise.all([
      api.get('/postulations'),
      api.get('/soutenances'),
    ])
    postulations.value = po.data.data.filter(p => Number(p.etudiant_id) === Number(etudiantId.value))
    soutenances.value  = so.data.data
  } catch {}
  loading.value = false
}

const monProjet = computed(() => {
  const accepted = postulations.value.find(p => p.statut === 'accepte')
  return accepted?.projet || null
})

const maSoutenance = computed(() =>
  monProjet.value ? soutenances.value.find(s => Number(s.projet_id) === Number(monProjet.value.id)) : null
)

const joursRestants = computed(() => {
  if (!maSoutenance.value) return null
  const d = new Date(maSoutenance.value.date)
  const now = new Date()
  return Math.ceil((d - now) / (1000 * 60 * 60 * 24))
})

const isPast = computed(() =>
  maSoutenance.value && new Date(maSoutenance.value.date) < new Date()
)

const statutLabel = { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }
const statutColor = { planifiee: 'bg-blue-100 text-blue-700', en_cours: 'bg-amber-100 text-amber-700', terminee: 'bg-green-100 text-green-700', annulee: 'bg-red-100 text-red-600' }

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Ma soutenance</h1>
      <p class="text-sm text-slate-400">Détails de votre présentation finale</p>
    </div>

    <div v-if="loading" class="rounded-[2rem] border border-white/70 bg-white/90 p-10 text-center text-sm text-slate-400 shadow-sm">Chargement…</div>

    <div v-else-if="!monProjet" class="rounded-[2rem] border-2 border-dashed border-slate-200 bg-white/60 p-10 text-center">
      <i class="fa-solid fa-graduation-cap text-5xl text-slate-300"></i>
      <p class="mt-4 text-base font-extrabold text-slate-700">Pas encore de projet</p>
      <p class="mt-1 text-sm text-slate-400">Une soutenance ne peut être planifiée qu'après l'acceptation de votre projet PFE.</p>
    </div>

    <div v-else-if="!maSoutenance" class="rounded-[2rem] border-2 border-dashed border-amber-200 bg-amber-50 p-10 text-center">
      <i class="fa-regular fa-calendar text-5xl text-amber-400"></i>
      <p class="mt-4 text-base font-extrabold text-amber-900">Soutenance non planifiée</p>
      <p class="mt-1 text-sm text-amber-700">Votre coordinateur n'a pas encore programmé votre soutenance.</p>
      <p class="mt-3 text-xs text-amber-600">Projet : <span class="font-bold">{{ monProjet.titre }}</span></p>
    </div>

    <div v-else class="space-y-5">
      <!-- Hero countdown -->
      <section class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#1e4a49] via-[#2a5e5d] to-[#3d7a6f] shadow-xl">
        <div class="relative z-10 p-8 sm:p-10 text-white">
          <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#d6e87a]">
            <i class="fa-solid fa-graduation-cap mr-2"></i>Soutenance
          </p>
          <div class="mt-4 flex flex-wrap items-end gap-8">
            <div v-if="!isPast">
              <p class="text-7xl font-extrabold leading-none text-[#d6e87a]">{{ joursRestants }}</p>
              <p class="text-sm font-bold uppercase tracking-widest text-white/70 mt-2">
                {{ joursRestants === 1 ? 'jour restant' : 'jours restants' }}
              </p>
            </div>
            <div v-else>
              <p class="text-3xl font-extrabold text-[#d6e87a]">Terminée</p>
              <p class="text-sm text-white/70 mt-1">Votre soutenance a eu lieu</p>
            </div>
            <div class="flex-1 min-w-[200px]">
              <p class="text-2xl font-extrabold leading-tight">
                {{ new Date(maSoutenance.date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) }}
              </p>
              <p class="mt-1 text-base font-semibold text-white/80">
                <i class="fa-regular fa-clock mr-2"></i>{{ maSoutenance.heure?.slice(0,5) }}
                <span class="mx-2 text-white/40">·</span>
                <i class="fa-solid fa-door-open mr-1.5"></i>Salle {{ maSoutenance.salle }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Detail cards -->
      <div class="grid gap-4 md:grid-cols-2">
        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Projet</p>
          <h3 class="text-base font-extrabold text-slate-900 leading-snug">{{ monProjet.titre }}</h3>
          <p class="mt-2 text-sm text-slate-500 line-clamp-3">{{ monProjet.description || '—' }}</p>
          <div class="mt-4 pt-4 border-t border-slate-100 space-y-1.5 text-xs text-slate-500">
            <p><i class="fa-solid fa-tags w-4 text-slate-400"></i>{{ monProjet.domaine || '—' }}</p>
            <p><i class="fa-solid fa-chalkboard-user w-4 text-slate-400"></i>
              {{ monProjet.professeur?.utilisateur?.prenom }} {{ monProjet.professeur?.utilisateur?.nom }}
            </p>
          </div>
        </article>

        <article class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
          <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Logistique</p>
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i class="fa-regular fa-calendar"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400">Date</p>
                <p class="text-sm font-bold text-slate-800">
                  {{ new Date(maSoutenance.date).toLocaleDateString('fr-FR') }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                <i class="fa-regular fa-clock"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400">Heure</p>
                <p class="text-sm font-bold text-slate-800">{{ maSoutenance.heure?.slice(0,5) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                <i class="fa-solid fa-door-open"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400">Salle</p>
                <p class="text-sm font-bold text-slate-800">{{ maSoutenance.salle }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl"
                :class="statutColor[maSoutenance.statut]">
                <i class="fa-solid fa-flag"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400">Statut</p>
                <p class="text-sm font-bold text-slate-800">{{ statutLabel[maSoutenance.statut] || maSoutenance.statut }}</p>
              </div>
            </div>
          </div>
        </article>
      </div>

      <!-- Jury -->
      <article v-if="maSoutenance.jury" class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Jury</p>
        <div class="flex flex-wrap gap-3">
          <span v-for="m in maSoutenance.jury.split(',')" :key="m"
            class="flex items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-700">
            <i class="fa-solid fa-user-tie text-[#1e4a49]"></i>{{ m.trim() }}
          </span>
        </div>
      </article>

      <!-- Final note if available -->
      <article v-if="maSoutenance.note_finale != null" class="rounded-[2rem] border-2 border-[#d6e87a] bg-[#f8faef] p-6 shadow-sm text-center">
        <p class="text-[10px] font-bold uppercase tracking-widest text-[#6a8a40] mb-2">Note finale</p>
        <p class="text-5xl font-extrabold text-[#1e4a49]">{{ maSoutenance.note_finale }}<span class="text-2xl text-slate-400">/20</span></p>
      </article>
    </div>
  </div>
</template>
