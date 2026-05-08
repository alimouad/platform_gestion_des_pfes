<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const loading = ref(false)
const stats = ref({ projets: 0, encadres: 0, depotsValides: 0 })

async function refresh() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const profId = user.value?.professeur?.id
    if (profId) {
      const [pr, po, de] = await Promise.all([
        api.get('/projets'),
        api.get('/postulations'),
        api.get('/depots'),
      ])
      const myProjets = pr.data.data.filter(p => p.professeur_id === profId)
      const ids = myProjets.map(p => p.id)
      const myAccepted = po.data.data.filter(p => ids.includes(p.projet_id) && p.statut === 'accepte')
      stats.value = {
        projets: myProjets.length,
        encadres: new Set(myAccepted.map(p => p.etudiant_id)).size,
        depotsValides: de.data.data.filter(d => ids.includes(d.projet_id) && d.statut_validation === 'valide').length,
      }
    }
  } catch {}
  loading.value = false
}

const initials = computed(() => {
  return ((user.value.prenom || 'P')[0] + (user.value.nom || 'R')[0]).toUpperCase()
})

const memberSince = computed(() => {
  if (!user.value.created_at) return '—'
  return new Date(user.value.created_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

onMounted(refresh)
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Mon profil</h1>
      <p class="text-sm text-slate-400">Informations académiques</p>
    </div>

    <article class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-indigo-700 via-blue-700 to-blue-900 shadow-xl">
      <div class="relative z-10 p-8 text-white">
        <div class="flex flex-wrap items-center gap-6">
          <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl font-black backdrop-blur-md border border-white/20 shadow-lg">
            {{ initials }}
          </div>
          <div class="flex-1 min-w-[200px]">
            <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-blue-200">
              {{ user.professeur?.grade || 'Professeur' }}
            </p>
            <h2 class="mt-2 text-3xl font-extrabold leading-tight">
              {{ user.prenom }} {{ user.nom }}
            </h2>
            <p class="mt-1 text-sm text-white/80">{{ user.courriel }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span v-if="user.professeur?.specialite" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-microscope mr-1.5"></i>{{ user.professeur.specialite }}
              </span>
              <span v-if="user.departement" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-building-columns mr-1.5"></i>{{ user.departement.nom }}
              </span>
              <span v-if="user.professeur?.bureau" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">
                <i class="fa-solid fa-door-open mr-1.5"></i>Bureau {{ user.professeur.bureau }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </article>

    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
          <span>Projets</span>
          <i class="fa-solid fa-folder-open text-slate-300"></i>
        </div>
        <p class="text-3xl font-extrabold text-slate-900">{{ stats.projets }}</p>
      </div>
      <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
          <span>Encadrés</span>
          <i class="fa-solid fa-users text-slate-300"></i>
        </div>
        <p class="text-3xl font-extrabold text-slate-900">{{ stats.encadres }}</p>
      </div>
      <div class="rounded-[2rem] border-2 border-blue-200 bg-blue-50 p-6 shadow-sm">
        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-blue-700 mb-2">
          <span>Validés</span>
          <i class="fa-solid fa-circle-check text-blue-600"></i>
        </div>
        <p class="text-3xl font-extrabold text-blue-900">{{ stats.depotsValides }}</p>
      </div>
    </div>

    <article class="rounded-[2rem] border border-white/70 bg-white/90 shadow-sm overflow-hidden">
      <div class="px-6 pt-5 pb-3">
        <p class="text-base font-extrabold">Informations</p>
        <p class="text-xs text-slate-400">Détails du compte</p>
      </div>
      <dl class="divide-y divide-slate-100">
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Nom complet</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.prenom }} {{ user.nom }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Email</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.courriel }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Grade</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.professeur?.grade || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Spécialité</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.professeur?.specialite || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Bureau</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.professeur?.bureau || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Département</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800">{{ user.departement?.nom || '—' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-4 px-6 py-4">
          <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Membre depuis</dt>
          <dd class="col-span-2 text-sm font-bold text-slate-800 capitalize">{{ memberSince }}</dd>
        </div>
      </dl>
    </article>
  </div>
</template>
