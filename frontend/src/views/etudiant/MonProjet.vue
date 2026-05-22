<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const user = ref(JSON.parse(localStorage.getItem('admin_user') || '{}'))
const etudiantId = computed(() => user.value?.etudiant?.id)

const projet = ref(null)
const depots = ref([])
const soutenance = ref(null)
const loading = ref(true)

const REQUIRED_TYPES = ['rapport', 'donnees', 'presentation']

async function fetchAll() {
  loading.value = true
  try {
    const me = await api.get('/me')
    user.value = me.data?.data || {}
    localStorage.setItem('admin_user', JSON.stringify(user.value))

    const [po, de, so] = await Promise.all([
      api.get('/postulations'),
      api.get('/depots'),
      api.get('/soutenances'),
    ])

    const accepted = po.data.data.find(p => p.etudiant_id === etudiantId.value && p.statut === 'accepte')
    if (!accepted) { loading.value = false; return }

    const pRes = await api.get(`/projets/${accepted.projet_id}`)
    projet.value = pRes.data.data

    depots.value = de.data.data.filter(d => d.projet_id === accepted.projet_id && d.etudiant_id === etudiantId.value)
    soutenance.value = so.data.data.find(s => s.projet_id === accepted.projet_id) || null
  } catch {}
  loading.value = false
}

const depotPct = computed(() => {
  const valides = REQUIRED_TYPES.filter(t => depots.value.some(d => d.type_depot === t && d.statut_validation === 'valide'))
  return Math.round((valides.length / REQUIRED_TYPES.length) * 100)
})

const stage = computed(() => {
  if (!projet.value) return 0
  if (soutenance.value?.statut === 'terminee') return 5
  if (soutenance.value) return 4
  if (depotPct.value === 100) return 3
  if (depots.value.length > 0) return 2
  return 1
})

const stages = [
  { label: 'Projet assigné',    icon: 'fa-folder-open',      active: 'bg-[#1e4a49] text-white',         inactive: 'bg-slate-100 text-slate-400' },
  { label: 'Dépôts en cours',   icon: 'fa-cloud-arrow-up',   active: 'bg-amber-500 text-white',          inactive: 'bg-slate-100 text-slate-400' },
  { label: 'Dépôts complets',   icon: 'fa-circle-check',     active: 'bg-[#4a7a30] text-white',          inactive: 'bg-slate-100 text-slate-400' },
  { label: 'Dépôts validés',    icon: 'fa-badge-check',      active: 'bg-green-600 text-white',          inactive: 'bg-slate-100 text-slate-400' },
  { label: 'Soutenance planif.', icon: 'fa-calendar-check',  active: 'bg-purple-600 text-white',         inactive: 'bg-slate-100 text-slate-400' },
  { label: 'Soutenu',           icon: 'fa-graduation-cap',   active: 'bg-[#d6e87a] text-[#1e4a49]',      inactive: 'bg-slate-100 text-slate-400' },
]

const typeConfig = {
  rapport:      { label: 'Rapport',       icon: 'fa-file-pdf',        bg: 'bg-red-50',    color: 'text-red-500' },
  donnees:      { label: 'Données',       icon: 'fa-file-code',       bg: 'bg-blue-50',   color: 'text-blue-500' },
  presentation: { label: 'Présentation',  icon: 'fa-file-powerpoint', bg: 'bg-orange-50', color: 'text-orange-500' },
}

const statutColor = {
  en_attente: 'bg-amber-100 text-amber-700',
  valide:     'bg-green-100 text-green-700',
  rejete:     'bg-red-100 text-red-600',
}
const statutLabel = { en_attente: 'En attente', valide: 'Validé', rejete: 'Rejeté' }

const soutenanceStatutColor = {
  planifiee: 'bg-blue-100 text-blue-700',
  en_cours:  'bg-amber-100 text-amber-700',
  terminee:  'bg-green-100 text-green-700',
  annulee:   'bg-red-100 text-red-600',
}
const soutenanceStatutLabel = { planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' }

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '—'
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6">

    <!-- Loading -->
    <div v-if="loading" class="rounded-3xl border border-white/70 bg-white/90 p-16 text-center text-sm text-slate-400">
      <i class="fa-solid fa-circle-notch fa-spin text-2xl text-[#d6e87a] mb-3 block"></i>
      Chargement de votre projet…
    </div>

    <!-- No project assigned -->
    <template v-else-if="!projet">
      <div class="rounded-3xl bg-[#1e4a49] px-8 py-8 text-white relative overflow-hidden">
        <div class="absolute -right-8 -top-8 h-44 w-44 rounded-full bg-white/5"></div>
        <div class="relative">
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Mon projet PFE</p>
          <h1 class="mt-1 text-2xl font-black">Aucun projet assigné</h1>
          <p class="mt-1 text-sm text-white/60">Postuloz à un projet pour qu'il apparaisse ici.</p>
        </div>
      </div>
      <div class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/60 py-24 text-center">
        <i class="fa-solid fa-folder-open text-5xl text-slate-300"></i>
        <p class="mt-4 text-base font-extrabold text-slate-700">Pas encore de projet</p>
        <p class="mt-1 text-sm text-slate-400">Votre postulation doit être acceptée pour accéder à cette page.</p>
        <router-link to="/etudiant/projets" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-[#1e4a49] px-6 py-2.5 text-sm font-black text-white hover:bg-[#163836] transition">
          <i class="fa-solid fa-compass"></i> Voir les projets disponibles
        </router-link>
      </div>
    </template>

    <!-- Project found -->
    <template v-else>

      <!-- Hero banner -->
      <div class="rounded-3xl bg-[#1e4a49] px-8 py-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-[#d6e87a]/10"></div>
        <div class="absolute bottom-0 left-16 h-16 w-16 rounded-full bg-white/5"></div>
        <div class="relative">
          <p class="text-[11px] font-black uppercase tracking-widest text-[#d6e87a]">Mon projet PFE</p>
          <h1 class="mt-1 text-2xl font-black leading-snug">{{ projet.titre }}</h1>
          <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-white/60">
            <span v-if="projet.domaine" class="flex items-center gap-1.5">
              <i class="fa-solid fa-tag text-[#d6e87a]"></i>{{ projet.domaine }}
            </span>
            <span v-if="projet.anneeUniversitaire" class="flex items-center gap-1.5">
              <i class="fa-solid fa-calendar text-[#d6e87a]"></i>{{ projet.anneeUniversitaire.annee }}
            </span>
            <span v-if="projet.filiere" class="flex items-center gap-1.5">
              <i class="fa-solid fa-layer-group text-[#d6e87a]"></i>{{ projet.filiere.nom }}
            </span>
          </div>
        </div>
      </div>

      <!-- Stage pipeline -->
      <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-5">Progression</p>
        <div class="flex items-center gap-0">
          <template v-for="(st, i) in stages" :key="i">
            <div class="flex flex-col items-center gap-1.5 flex-1">
              <div class="flex h-10 w-10 items-center justify-center rounded-full shadow-sm transition-all"
                :class="i <= stage ? st.active : st.inactive">
                <i :class="`fa-solid ${st.icon} text-xs`"></i>
              </div>
              <span class="text-[9px] font-bold text-center leading-tight text-slate-400 w-16">{{ st.label }}</span>
            </div>
            <div v-if="i < stages.length - 1"
              class="h-0.5 flex-1 mb-5 transition-all"
              :class="i < stage ? 'bg-[#d6e87a]' : 'bg-slate-200'"></div>
          </template>
        </div>
      </div>

      <!-- Info grid -->
      <div class="grid gap-5 md:grid-cols-2">

        <!-- Project details -->
        <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6 space-y-4">
          <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Détails du projet</p>

          <div class="space-y-3">
            <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Titre</p>
              <p class="text-sm font-black text-slate-800">{{ projet.titre }}</p>
            </div>
            <div v-if="projet.description" class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Description</p>
              <p class="text-sm text-slate-700 leading-relaxed">{{ projet.description }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Domaine</p>
                <p class="text-sm font-bold text-slate-800">{{ projet.domaine || '—' }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Statut</p>
                <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-bold"
                  :class="projet.statut === 'valide' ? 'bg-green-100 text-green-700'
                    : projet.statut === 'en_cours' ? 'bg-amber-100 text-amber-700'
                    : projet.statut === 'soutenu' ? 'bg-[#d6e87a] text-slate-800'
                    : 'bg-slate-100 text-slate-600'">
                  {{ { brouillon:'Brouillon', soumis:'Soumis', en_cours:'En cours', valide:'Validé', soutenu:'Soutenu', rejete:'Rejeté' }[projet.statut] || projet.statut }}
                </span>
              </div>
            </div>
            <div v-if="projet.ville" class="rounded-2xl bg-[#f0f3eb] border border-[#d6e87a]/40 px-4 py-3 flex items-center gap-2">
              <i class="fa-solid fa-location-dot text-[#d6e87a]"></i>
              <p class="text-sm font-semibold text-[#4a5e20]">{{ projet.ville }}</p>
            </div>
          </div>

          <!-- Encadrant -->
          <div v-if="projet.professeur">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Encadrant</p>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#1e4a49] text-sm font-black text-[#d6e87a]">
                {{ (projet.professeur.utilisateur?.prenom?.[0] || '') }}{{ (projet.professeur.utilisateur?.nom?.[0] || '') }}
              </div>
              <div>
                <p class="text-sm font-black text-slate-800">{{ projet.professeur.utilisateur?.prenom }} {{ projet.professeur.utilisateur?.nom }}</p>
                <p class="text-xs text-slate-400">{{ projet.professeur.utilisateur?.courriel }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Depots + Soutenance -->
        <div class="space-y-5">

          <!-- Depots -->
          <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Mes dépôts</p>
              <span class="text-xs font-black" :class="depotPct === 100 ? 'text-[#4a7a30]' : 'text-slate-500'">
                {{ depotPct }}% validé
              </span>
            </div>

            <!-- Progress bar -->
            <div class="h-2 w-full rounded-full bg-slate-100 mb-4">
              <div class="h-full rounded-full transition-all duration-700"
                :class="depotPct === 100 ? 'bg-[#d6e87a]' : 'bg-amber-400'"
                :style="`width:${depotPct}%`"></div>
            </div>

            <div class="space-y-2">
              <div v-for="type in REQUIRED_TYPES" :key="type">
                <template v-if="depots.filter(d => d.type_depot === type).length">
                  <div v-for="d in depots.filter(d => d.type_depot === type)" :key="d.id"
                    class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="typeConfig[type].bg">
                      <i :class="`fa-solid ${typeConfig[type].icon} text-sm ${typeConfig[type].color}`"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-black text-slate-800">{{ typeConfig[type].label }}</p>
                      <p class="text-[10px] text-slate-400">{{ fmtDate(d.depose_le || d.created_at) }}</p>
                      <p v-if="d.commentaire" class="mt-1 rounded-lg bg-red-50 border border-red-100 px-2 py-1 text-[10px] italic text-red-600">« {{ d.commentaire }} »</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                      <span class="rounded-lg px-2.5 py-1 text-[10px] font-black" :class="statutColor[d.statut_validation]">
                        {{ statutLabel[d.statut_validation] }}
                      </span>
                      <a :href="d.chemin_fichier" target="_blank" rel="noopener"
                        class="text-[10px] font-bold text-slate-400 hover:text-[#1e4a49] transition flex items-center gap-1">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Ouvrir
                      </a>
                    </div>
                  </div>
                </template>
                <div v-else class="flex items-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                    <i :class="`fa-solid ${typeConfig[type].icon} text-sm text-slate-300`"></i>
                  </div>
                  <div>
                    <p class="text-xs font-black text-slate-400">{{ typeConfig[type].label }}</p>
                    <p class="text-[10px] text-slate-300 italic">Non déposé</p>
                  </div>
                  <span class="ml-auto rounded-xl bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-400">Manquant</span>
                </div>
              </div>
            </div>

            <router-link to="/etudiant/depots"
              class="mt-4 flex items-center justify-center gap-2 rounded-2xl bg-[#1e4a49] py-2.5 text-sm font-black text-white hover:bg-[#163836] transition">
              <i class="fa-solid fa-cloud-arrow-up"></i> Déposer un fichier
            </router-link>
          </div>

          <!-- Soutenance -->
          <div class="rounded-3xl border border-white/70 bg-white/90 shadow-sm p-6">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-4">Soutenance</p>

            <div v-if="soutenance" class="space-y-3">
              <div class="flex items-center gap-2 mb-3">
                <span class="rounded-full px-3 py-1 text-[11px] font-black" :class="soutenanceStatutColor[soutenance.statut]">
                  {{ soutenanceStatutLabel[soutenance.statut] }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Date</p>
                  <p class="text-sm font-black text-slate-800 capitalize">{{ fmtDate(soutenance.date) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Heure</p>
                  <p class="text-sm font-black text-slate-800">{{ soutenance.heure?.slice(0,5) || '—' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Salle</p>
                  <p class="text-sm font-black text-slate-800">{{ soutenance.salle || '—' }}</p>
                </div>
                <div v-if="soutenance.jury" class="rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Jury</p>
                  <p class="text-sm font-bold text-slate-800 leading-snug">{{ soutenance.jury }}</p>
                </div>
              </div>
              <div v-if="soutenance.note_finale != null"
                class="rounded-2xl border-2 border-[#d6e87a] bg-[#f8faef] p-5 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#6a8a40] mb-1">Note finale</p>
                <p class="text-5xl font-black text-[#1e4a49]">
                  {{ soutenance.note_finale }}<span class="text-2xl text-slate-400 font-bold">/20</span>
                </p>
              </div>
            </div>

            <div v-else class="flex flex-col items-center py-6 text-center">
              <i class="fa-solid fa-calendar-xmark text-3xl text-slate-300"></i>
              <p class="mt-3 text-sm font-bold text-slate-500">Aucune soutenance planifiée</p>
              <p class="text-xs text-slate-400 mt-1">Elle sera fixée par le coordinateur.</p>
            </div>
          </div>

        </div>
      </div>

    </template>
  </div>
</template>
