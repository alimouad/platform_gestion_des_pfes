<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const error = ref(false)

const stats = ref({ etudiants: 0, professeurs: 0, projets: 0, departements: 0 })
const projets = ref([])
const soutenances = ref([])
const annees = ref([])

// Vibrant palette matching the OAK design system
const domaineColors = ['#d6e87a', '#a8c47c', '#7aab7c', '#4a8c6c', '#1a6c5c', '#f0cc7d', '#e8a87a']

const projetsParDomaine = computed(() => {
  if (!projets.value.length) return []
  const map = {}
  projets.value.forEach(p => { map[p.domaine] = (map[p.domaine] || 0) + 1 })
  const total = projets.value.length
  
  return Object.entries(map).map(([label, count], i) => ({
    label, 
    count,
    pct: Math.round((count / total) * 100),
    color: domaineColors[i % domaineColors.length],
  })).sort((a, b) => b.count - a.count)
})

const projetsParStatut = computed(() => {
  const map = { brouillon: 0, soumis: 0, en_cours: 0, valide: 0, soutenu: 0, rejete: 0 }
  projets.value.forEach(p => { if (map[p.statut] !== undefined) map[p.statut]++ })
  return map
})

const recentProjets = computed(() => [...projets.value].reverse().slice(0, 5))

const upcomingSoutenances = computed(() =>
  soutenances.value
    .filter(s => new Date(s.date) >= new Date().setHours(0,0,0,0))
    .sort((a, b) => new Date(a.date) - new Date(b.date))
    .slice(0, 5)
)

const statutLabel = { brouillon: 'Brouillon', soumis: 'Soumis', en_cours: 'En cours', valide: 'Validé', soutenu: 'Soutenu', rejete: 'Rejeté' }
const statutColor = { 
  brouillon: 'bg-slate-100 text-slate-600', 
  soumis: 'bg-blue-100 text-blue-700', 
  en_cours: 'bg-amber-100 text-amber-700', 
  valide: 'bg-emerald-100 text-emerald-700', 
  soutenu: 'bg-[#d6e87a] text-slate-800', 
  rejete: 'bg-red-100 text-red-600' 
}

async function fetchAll() {
  loading.value = true
  error.value = false
  try {
    const [e, p, pr, d, s, a] = await Promise.allSettled([
      api.get('/etudiants'),
      api.get('/professeurs'),
      api.get('/projets'),
      api.get('/departements'),
      api.get('/soutenances'),
      api.get('/annees-universitaires'),
    ])

    const etudiants = e.status === 'fulfilled' ? e.value.data.data : []
    const professeurs = p.status === 'fulfilled' ? p.value.data.data : []
    const projetsData = pr.status === 'fulfilled' ? pr.value.data.data : []
    const departements = d.status === 'fulfilled' ? d.value.data.data : []

    stats.value = {
      etudiants: etudiants.length,
      professeurs: professeurs.length,
      projets: projetsData.length,
      departements: departements.length,
    }
    projets.value = projetsData
    soutenances.value = s.status === 'fulfilled' ? s.value.data.data : []
    annees.value = a.status === 'fulfilled' ? a.value.data.data : []
  } catch (err) {
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(fetchAll)
</script>

<template>
  <div class="space-y-6 pb-10">
    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-[3rem] bg-[#1e4a49] shadow-2xl transition-all duration-500">
      <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=2070&q=80"
           class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay" alt="" />
      <div class="absolute inset-0 bg-gradient-to-br from-[#1e4a49] via-[#1e4a49]/80 to-transparent"></div>
      
      <div class="relative z-10 p-8 sm:p-12">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <div class="mb-4 inline-flex items-center rounded-full bg-white/10 px-3 py-1 backdrop-blur-md">
              <span class="mr-2 h-2 w-2 animate-ping rounded-full bg-[#d6e87a]"></span>
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/80">FSBM University — Super Admin</p>
            </div>
            <h1 class="text-4xl font-black leading-tight tracking-tight text-white sm:text-6xl">
              Tableau de <span class="text-[#d6e87a]">bord</span>
            </h1>
          </div>
          
          <div class="flex flex-wrap gap-4">
            <button @click="router.push('/admin/etudiants')"
              class="group flex items-center rounded-2xl bg-[#d6e87a] px-6 py-4 text-sm font-black text-slate-900 shadow-xl transition-all hover:scale-105 active:scale-95">
              <i class="fa-solid fa-plus mr-2 opacity-50 group-hover:rotate-90 transition-transform"></i> Étudiant
            </button>
            <button @click="router.push('/admin/utilisateurs')"
              class="flex items-center rounded-2xl bg-white/10 px-6 py-4 text-sm font-black text-white backdrop-blur-md border border-white/20 transition-all hover:bg-white/20">
              <i class="fa-solid fa-user-plus mr-2 opacity-50"></i> Enseignant
            </button>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="item in [
            { label:'Étudiants', value: stats.etudiants, icon:'fa-user-graduate', color:'text-blue-500' },
            { label:'Professeurs', value: stats.professeurs, icon:'fa-chalkboard-user', color:'text-emerald-500' },
            { label:'Projets PFE', value: stats.projets, icon:'fa-folder-tree', color:'text-amber-500' },
            { label:'Départements', value: stats.departements, icon:'fa-landmark', color:'text-purple-500' },
          ]" :key="item.label"
            class="group relative overflow-hidden rounded-[2rem] bg-white p-6 transition-all hover:-translate-y-1 hover:shadow-2xl">
            <div class="flex justify-between items-start">
              <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center transition-colors group-hover:bg-[#d6e87a]/20">
                <i :class="`fa-solid ${item.icon} ${item.color}`"></i>
              </div>
              <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ item.label }}</span>
            </div>
            <div class="mt-4">
              <div v-if="loading" class="h-8 w-24 animate-pulse rounded-lg bg-slate-100"></div>
              <p v-else class="text-3xl font-black text-slate-900">{{ item.value.toLocaleString() }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Layout -->
    <div class="grid gap-6 xl:grid-cols-12">
      
      <!-- Center Content -->
      <div class="xl:col-span-8 space-y-6">
        
        <!-- Status Pills -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <div v-for="(count, statut) in projetsParStatut" :key="statut"
            class="group flex flex-col items-center justify-center rounded-3xl border border-white bg-white/60 p-4 shadow-sm transition-all hover:bg-white hover:shadow-md">
            <span class="text-[10px] font-bold uppercase tracking-tighter text-slate-400">{{ statutLabel[statut] }}</span>
            <span v-if="loading" class="mt-1 h-6 w-8 animate-pulse rounded bg-slate-200"></span>
            <span v-else class="text-xl font-black text-slate-800 transition-transform group-hover:scale-110">{{ count }}</span>
          </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <!-- Thematic Distribution -->
          <article class="rounded-[2.5rem] bg-white p-8 shadow-sm border border-slate-100">
            <div class="mb-6">
              <h3 class="text-xl font-black text-slate-800">Domaines</h3>
              <p class="text-xs font-medium text-slate-400">Répartition des sujets de recherche</p>
            </div>
            
            <div v-if="projetsParDomaine.length" class="flex flex-col items-center gap-8 lg:flex-row">
              <div class="relative shrink-0 scale-110">
                <div class="h-36 w-36 rounded-full transition-transform duration-1000" :style="{
                  background: `conic-gradient(${projetsParDomaine.map((d,i) => {
                    const prev = projetsParDomaine.slice(0,i).reduce((a,b)=>a+b.pct,0)
                    return `${d.color} ${prev}% ${prev+d.pct}%`
                  }).join(', ')})`
                }"></div>
                <div class="absolute inset-0 m-auto flex h-[70%] w-[70%] flex-col items-center justify-center rounded-full bg-white shadow-inner">
                  <span class="text-2xl font-black text-slate-800">{{ stats.projets }}</span>
                  <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Projets</span>
                </div>
              </div>
              <div class="grid flex-1 grid-cols-1 gap-3 w-full">
                <div v-for="d in projetsParDomaine" :key="d.label" class="flex items-center group">
                  <span class="mr-3 h-3 w-3 rounded-full shrink-0 shadow-sm" :style="{background: d.color}"></span>
                  <span class="truncate text-xs font-bold text-slate-600 group-hover:text-slate-900 transition-colors">{{ d.label }}</span>
                  <div class="ml-auto flex items-center gap-2">
                    <span class="text-[10px] font-black text-slate-800">{{ d.pct }}%</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="flex h-48 items-center justify-center text-sm text-slate-400 italic">
              Données insuffisantes
            </div>
          </article>

          <!-- Project State Progress -->
          <article class="rounded-[2.5rem] bg-white p-8 shadow-sm border border-slate-100">
            <h3 class="text-xl font-black text-slate-800">Progression</h3>
            <p class="mb-8 text-xs font-medium text-slate-400">État d'avancement global</p>
            
            <div class="space-y-5">
              <div v-for="(count, statut) in projetsParStatut" :key="statut">
                <div class="mb-1.5 flex justify-between px-1">
                  <span class="text-[10px] font-black uppercase text-slate-500">{{ statutLabel[statut] }}</span>
                  <span class="text-[10px] font-black text-slate-900">{{ count }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-50">
                  <div class="h-full rounded-full bg-[#d6e87a] transition-all duration-1000 ease-out"
                       :style="{ width: stats.projets ? `${(count/stats.projets)*100}%` : '0%' }">
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Project Table -->
        <article class="overflow-hidden rounded-[2.5rem] bg-white shadow-sm border border-slate-100">
          <div class="flex items-center justify-between p-8">
            <h3 class="text-xl font-black text-slate-800">Derniers Projets</h3>
            <button @click="router.push('/admin/projets')" class="rounded-xl bg-slate-900 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest text-white transition-all hover:bg-slate-700 active:scale-95">
              Tout voir
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50/50">
                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                  <th class="px-8 py-4">Titre du PFE</th>
                  <th class="px-4 py-4">Domaine</th>
                  <th class="px-4 py-4">Statut</th>
                  <th class="px-8 py-4 text-right">Encadrant</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="p in recentProjets" :key="p.id" class="group transition-colors hover:bg-slate-50/50">
                  <td class="px-8 py-5">
                    <p class="max-w-[240px] truncate text-sm font-bold text-slate-800 group-hover:text-[#1e4a49] transition-colors">{{ p.titre }}</p>
                  </td>
                  <td class="px-4 py-5 text-xs font-medium text-slate-500">{{ p.domaine }}</td>
                  <td class="px-4 py-5">
                    <span :class="statutColor[p.statut]" class="inline-block rounded-lg px-2.5 py-1 text-[10px] font-black">
                      {{ statutLabel[p.statut] }}
                    </span>
                  </td>
                  <td class="px-8 py-5 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <span class="text-xs font-bold text-slate-700">{{ p.professeur?.utilisateur?.prenom }} {{ p.professeur?.utilisateur?.nom }}</span>
                      <div class="h-7 w-7 rounded-full bg-[#d6e87a]/20 flex items-center justify-center text-[10px] font-bold text-[#1e4a49]">
                        {{ p.professeur?.utilisateur?.nom?.charAt(0) }}
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- Right Column -->
      <aside class="xl:col-span-4 space-y-6">
        
        <!-- Schedule -->
        <article class="rounded-[2.5rem] bg-white p-8 shadow-sm border border-slate-100">
          <div class="mb-8 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-800">Agenda</h3>
            <button @click="router.push('/admin/soutenances')" class="h-8 w-8 rounded-full border border-slate-100 flex items-center justify-center hover:bg-[#d6e87a] transition-colors">
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
          </div>
          
          <div class="space-y-4">
            <div v-if="upcomingSoutenances.length === 0" class="flex flex-col items-center justify-center py-10 opacity-40">
              <i class="fa-solid fa-calendar-xmark text-4xl mb-2"></i>
              <p class="text-xs font-bold uppercase">Aucun événement</p>
            </div>
            <div v-for="s in upcomingSoutenances" :key="s.id"
              class="group relative rounded-3xl border border-slate-50 bg-slate-50/30 p-5 transition-all hover:border-[#d6e87a] hover:bg-white hover:shadow-xl">
              <div class="mb-2 flex items-center justify-between">
                <span class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-[#1e4a49] shadow-sm uppercase">
                  {{ new Date(s.date).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' }) }}
                </span>
                <span class="text-[10px] font-bold text-slate-400"><i class="fa-regular fa-clock mr-1"></i>{{ s.heure?.slice(0,5) }}</span>
              </div>
              <h4 class="line-clamp-2 text-sm font-black text-slate-800 group-hover:text-[#1e4a49]">{{ s.projet?.titre }}</h4>
              <div class="mt-4 flex items-center text-[10px] font-bold text-slate-400">
                <i class="fa-solid fa-location-dot mr-1.5 text-[#d6e87a]"></i> Salle {{ s.salle }}
              </div>
            </div>
          </div>
        </article>

        <!-- Academic Years -->
        <article class="rounded-[2.5rem] bg-slate-900 p-8 shadow-xl text-white">
          <h3 class="text-xl font-black">Cycles Académiques</h3>
          <p class="mb-6 text-xs font-medium text-white/40 font-mono tracking-tighter">Academic Lifecycle Management</p>
          
          <div class="space-y-3">
            <div v-if="annees.length === 0" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/50">
              Aucune année universitaire disponible.
            </div>
            <div v-for="a in annees" :key="a.id"
              class="flex items-center justify-between rounded-2xl bg-white/5 p-4 border border-white/5 hover:bg-white/10 transition-colors cursor-default">
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#d6e87a] text-slate-900">
                  <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                  <p class="text-sm font-black tracking-tight">{{ a.annee }}</p>
                  <p class="text-[9px] uppercase font-bold text-white/40">Saison universitaire</p>
                </div>
              </div>
              <span v-if="a.statut === 'active'" class="h-2 w-2 rounded-full bg-[#d6e87a] shadow-[0_0_12px_rgba(214,232,122,0.5)]"></span>
            </div>
          </div>
          
          <button @click="router.push('/admin/annees')"
            class="mt-6 w-full rounded-2xl border border-dashed border-white/20 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-white/60 transition-all hover:border-[#d6e87a] hover:text-white">
            Configurer les cycles
          </button>
        </article>
      </aside>
    </div>
  </div>
</template>

<style scoped>
/* Optional: Smooth transition for the donut chart */
.conic-gradient {
  transition: background 0.5s ease-in-out;
}

/* Custom Scrollbar for the table */
::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
</style>
