import { createRouter, createWebHistory } from 'vue-router';
import axiosClient from '@/services/api.js';
import Login from '@/views/auth/Login.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            redirect: '/login',
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { guest: true },
        },
        {
            path: '/admin',
            component: () => import('@/layouts/AdminLayout.vue'),
            meta: { requiresAuth: true, role: 'superadmin' },
            children: [
                { path: '', redirect: '/admin/dashboard' },
                { path: 'dashboard',    name: 'admin.dashboard',    component: () => import('@/views/admin/Dashboard.vue') },
                { path: 'utilisateurs', name: 'admin.utilisateurs', component: () => import('@/views/admin/Utilisateurs.vue') },
                { path: 'etudiants',    name: 'admin.etudiants',    component: () => import('@/views/admin/Etudiants.vue') },
                { path: 'professeurs',  name: 'admin.professeurs',  component: () => import('@/views/admin/Professeurs.vue') },
                { path: 'projets',      name: 'admin.projets',      component: () => import('@/views/admin/Projets.vue') },
                { path: 'departements', name: 'admin.departements', component: () => import('@/views/admin/Departements.vue') },
                { path: 'annees',       name: 'admin.annees',       component: () => import('@/views/admin/AnneesUniversitaires.vue') },
                { path: 'soutenances',  name: 'admin.soutenances',  component: () => import('@/views/admin/Soutenances.vue') },
                { path: 'statistiques', name: 'admin.statistiques', component: () => import('@/views/admin/Statistiques.vue') },
            ],
        },
        {
            path: '/coordinateur',
            component: () => import('@/layouts/CoordinateurLayout.vue'),
            meta: { requiresAuth: true, role: 'coordinateur' },
            children: [
                { path: '', redirect: '/coordinateur/dashboard' },
                { path: 'dashboard',    name: 'coordinateur.dashboard',    component: () => import('@/views/coordinateur/Dashboard.vue') },
                { path: 'projets',      name: 'coordinateur.projets',      component: () => import('@/views/coordinateur/Projets.vue') },
                { path: 'postulations', name: 'coordinateur.postulations', component: () => import('@/views/coordinateur/Postulations.vue') },
                { path: 'depots',       name: 'coordinateur.depots',       component: () => import('@/views/coordinateur/Depots.vue') },
                { path: 'soutenances',  name: 'coordinateur.soutenances',  component: () => import('@/views/coordinateur/Soutenances.vue') },
                { path: 'etudiants',    name: 'coordinateur.etudiants',    component: () => import('@/views/coordinateur/Etudiants.vue') },
                { path: 'statistiques', name: 'coordinateur.statistiques', component: () => import('@/views/coordinateur/Statistiques.vue') },
            ],
        },
        {
            path: '/etudiant',
            component: () => import('@/layouts/EtudiantLayout.vue'),
            meta: { requiresAuth: true, role: 'etudiant' },
            children: [
                { path: '', redirect: '/etudiant/dashboard' },
                { path: 'dashboard',    name: 'etudiant.dashboard',    component: () => import('@/views/etudiant/Dashboard.vue') },
                { path: 'projets',      name: 'etudiant.projets',      component: () => import('@/views/etudiant/Projets.vue') },
                { path: 'postulations', name: 'etudiant.postulations', component: () => import('@/views/etudiant/Postulations.vue') },
                { path: 'depots',       name: 'etudiant.depots',       component: () => import('@/views/etudiant/Depots.vue') },
                { path: 'sig',          name: 'etudiant.sig',          component: () => import('@/views/etudiant/Sig.vue') },
                { path: 'soutenance',   name: 'etudiant.soutenance',   component: () => import('@/views/etudiant/Soutenance.vue') },
                { path: 'profil',       name: 'etudiant.profil',       component: () => import('@/views/etudiant/Profil.vue') },
            ],
        },
        {
            path: '/professeur',
            component: () => import('@/layouts/ProfesseurLayout.vue'),
            meta: { requiresAuth: true, role: 'professeur' },
            children: [
                { path: '', redirect: '/professeur/dashboard' },
                { path: 'dashboard',    name: 'professeur.dashboard',    component: () => import('@/views/professeur/Dashboard.vue') },
                { path: 'projets',      name: 'professeur.projets',      component: () => import('@/views/professeur/Projets.vue') },
                { path: 'postulations', name: 'professeur.postulations', component: () => import('@/views/professeur/Postulations.vue') },
                { path: 'depots',       name: 'professeur.depots',       component: () => import('@/views/professeur/Depots.vue') },
                { path: 'etudiants',    name: 'professeur.etudiants',    component: () => import('@/views/professeur/Etudiants.vue') },
                { path: 'profil',       name: 'professeur.profil',       component: () => import('@/views/professeur/Profil.vue') },
            ],
        },
    ],
});

const dashboardForRole = (role) => {
    if (role === 'superadmin')   return { name: 'admin.dashboard' };
    if (role === 'coordinateur') return { name: 'coordinateur.dashboard' };
    if (role === 'etudiant')     return { name: 'etudiant.dashboard' };
    if (role === 'professeur')   return { name: 'professeur.dashboard' };
    return { name: 'login' };
};

router.beforeEach(async (to) => {
    const token = localStorage.getItem('auth_token');

    if (to.meta.guest) {
        if (!token) return true;
        try {
            const res = await axiosClient.get('/me');
            return dashboardForRole(res.data?.data?.role);
        } catch {
            return true;
        }
    }

    if (!to.meta.requiresAuth) return true;

    if (!token) return { name: 'login' };

    try {
        const res = await axiosClient.get('/me');
        const role = res.data?.data?.role ?? '';
        if (to.meta.role && role !== to.meta.role) return dashboardForRole(role);
        return true;
    } catch {
        return { name: 'login' };
    }
});

export default router;
