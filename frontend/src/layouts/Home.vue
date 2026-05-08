<script setup>
const navItems = [
  { symbol: '⌂', label: 'Home', active: true },
  { symbol: '✦', label: 'Messages', active: false },
  { symbol: '◌', label: 'Students', active: false },
  { symbol: '▦', label: 'Reports', active: false },
]

const summaryCards = [
  {
    label: 'Students',
    value: '2,635',
    tone: 'bg-white/90 text-slate-900',
    symbol: '✦',
  },
  {
    label: 'Teachers',
    value: '29',
    tone: 'bg-[#eef3dd] text-slate-900',
    symbol: '◌',
  },
  {
    label: 'Faculty',
    value: '60',
    tone: 'bg-white/90 text-slate-900',
    symbol: '▤',
  },
]

const chartBars = [
  { month: 'Apr', height: '34%', active: false },
  { month: 'May', height: '78%', active: true },
  { month: 'Jun', height: '60%', active: false },
  { month: 'Jul', height: '96%', active: true },
  { month: 'Aug', height: '70%', active: false },
  { month: 'Sep', height: '92%', active: true },
  { month: 'Oct', height: '84%', active: true },
  { month: 'Nov', height: '88%', active: true },
  { month: 'Dec', height: '94%', active: true },
]

const teachers = [
  {
    name: 'Alex John',
    role: 'Physics Department',
    avatar: 'https://i.pravatar.cc/150?u=teacher-a',
  },
  {
    name: 'Maria Smith',
    role: 'Pure Mathematics',
    avatar: 'https://i.pravatar.cc/150?u=teacher-b',
  },
]

const events = [
  {
    date: 'Monday 22nd Jun, 2026',
    title: 'Model test of 11th class',
  },
  {
    date: 'Friday 15th Aug, 2026',
    title: 'Final project presentation',
  },
  {
    date: 'Tuesday 19th Aug, 2026',
    title: 'Final exam for Introduction to Psychology',
  },
  {
    date: 'Friday 3rd Sep, 2026',
    title: 'Practical assessment in Chemistry',
  },
  {
    date: 'Wednesday 12th Oct, 2026',
    title: 'Group project presentation for History 101',
  },
]
</script>

<template>
  <div class="relative min-h-screen overflow-hidden text-slate-900">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="absolute -left-8 top-0 select-none text-[18vw] font-black tracking-[0.28em] text-[#dce5cf]/75">
        EDUCATION
      </div>
      <div class="absolute right-0 top-20 h-[32rem] w-[32rem] rounded-full bg-[#bfd0a8]/35 blur-3xl"></div>
      <div class="absolute bottom-0 left-16 h-64 w-64 rounded-full bg-white/50 blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-[1720px] gap-5 px-4 py-4 lg:px-6 lg:py-5">
      <aside class="flex w-20 flex-col items-center justify-between rounded-[2rem] border border-white/60 bg-white/80 py-6 shadow-[0_20px_60px_rgba(95,118,88,0.14)] backdrop-blur-xl">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#dce7ca] text-lg font-extrabold text-[#61714f]">
          O
        </div>

        <nav class="flex flex-col items-center gap-4">
          <button
            v-for="item in navItems"
            :key="item.label"
            type="button"
            :aria-label="item.label"
            class="flex h-12 w-12 items-center justify-center rounded-2xl border text-lg transition-all"
            :class="item.active ? 'border-[#dce7ca] bg-[#eaf0d9] text-slate-900 shadow-sm' : 'border-transparent bg-transparent text-slate-400 hover:border-white hover:bg-white hover:text-slate-900'"
          >
            {{ item.symbol }}
          </button>
        </nav>

        <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-transparent text-slate-400 transition hover:border-white hover:bg-white hover:text-slate-900" type="button" aria-label="Settings">
          ⚙
        </button>
      </aside>

      <main class="min-w-0 flex-1 space-y-5">
        <section class="relative overflow-hidden rounded-[2.75rem] border border-white/60 bg-[#1e4a49] shadow-[0_34px_90px_rgba(20,51,49,0.22)]">
          <img
            class="absolute inset-0 h-full w-full object-cover opacity-55"
            src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=2070&q=80"
            alt="Education dashboard backdrop"
          />
          <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(17,35,33,0.78)_0%,rgba(17,35,33,0.28)_45%,rgba(17,35,33,0.1)_100%)]"></div>

          <div class="relative z-10 flex min-h-[30rem] flex-col justify-between gap-6 p-6 sm:p-8 lg:p-10">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
              <div class="space-y-4 text-white">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.35em] text-white/85 backdrop-blur-sm">
                  OAK University
                </div>
                <h1 class="max-w-xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                  A calm, modern academic dashboard inspired by the mockup.
                </h1>
                <p class="max-w-lg text-sm text-white/78 sm:text-base">
                  A clean home screen for student, teacher, and university operations with soft green accents, glass cards, and compact analytics.
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 rounded-full border border-white/18 bg-white/92 px-4 py-2 text-sm shadow-lg shadow-black/10 backdrop-blur-sm">
                  <span class="text-slate-400">⌕</span>
                  <input
                    class="w-44 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none sm:w-64"
                    type="text"
                    placeholder="Search here"
                  />
                </div>
                <button class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/92 text-slate-700 shadow-lg shadow-black/10 backdrop-blur-sm" type="button" aria-label="Calendar">
                  ◔
                </button>
                <button class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/92 text-slate-700 shadow-lg shadow-black/10 backdrop-blur-sm" type="button" aria-label="Notifications">
                  ◍
                </button>
                <img
                  class="h-11 w-11 rounded-full border-2 border-white/80 object-cover shadow-lg shadow-black/20"
                  src="https://i.pravatar.cc/150?u=oak-university"
                  alt="User avatar"
                />
              </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[1fr_19rem] xl:items-end">
              <div class="grid gap-3 sm:grid-cols-3">
                <article
                  v-for="card in summaryCards"
                  :key="card.label"
                  class="rounded-[2rem] border border-white/40 px-5 py-5 shadow-[0_18px_45px_rgba(13,28,27,0.16)] backdrop-blur-md"
                  :class="card.tone"
                >
                  <div class="mb-4 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500">
                    <span>{{ card.label }}</span>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/80 text-xs text-slate-700">{{ card.symbol }}</span>
                  </div>
                  <div class="text-3xl font-extrabold tracking-tight">{{ card.value }}</div>
                </article>
              </div>

              <article class="rounded-[2.3rem] bg-[#dbe88d] p-5 shadow-[0_18px_50px_rgba(80,96,35,0.22)]">
                <p class="text-sm font-extrabold text-slate-900">Add New Members</p>
                <p class="mt-1 text-xs text-slate-700/80">Quick access for students and teachers.</p>
                <div class="mt-4 flex gap-3">
                  <button class="flex-1 rounded-full bg-white px-4 py-3 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-700 shadow-sm" type="button">
                    + Student
                  </button>
                  <button class="flex-1 rounded-full bg-white px-4 py-3 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-700 shadow-sm" type="button">
                    + Teacher
                  </button>
                </div>
              </article>
            </div>
          </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.55fr_1fr]">
          <div class="grid gap-5">
            <div class="grid gap-5 md:grid-cols-2">
              <article class="rounded-[2.5rem] border border-white/70 bg-white/92 p-6 shadow-[0_18px_50px_rgba(98,120,92,0.12)] backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-base font-extrabold">Growth</p>
                    <p class="text-xs text-slate-400">Monthly overview</p>
                  </div>
                  <button class="rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-500" type="button">
                    Monthly ▾
                  </button>
                </div>
                <div class="mt-6 flex items-center justify-center">
                  <div class="relative flex h-52 w-52 items-center justify-center rounded-full" :style="{ background: `conic-gradient(#dbe88d 0 62%, #edf1e7 62% 100%)` }">
                    <div class="flex h-[78%] w-[78%] items-center justify-center rounded-full bg-white shadow-inner">
                      <div class="text-center">
                        <div class="text-4xl font-extrabold tracking-tight">62%</div>
                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.35em] text-slate-400">Academy</div>
                      </div>
                    </div>
                  </div>
                </div>
              </article>

              <article class="rounded-[2.5rem] border border-white/70 bg-white/92 p-6 shadow-[0_18px_50px_rgba(98,120,92,0.12)] backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-base font-extrabold">Students Attendance</p>
                    <p class="text-xs text-slate-400">Monthly trend</p>
                  </div>
                  <button class="rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-500" type="button">
                    Monthly ▾
                  </button>
                </div>

                <div class="mt-6 flex h-48 items-end gap-3">
                  <div v-for="bar in chartBars" :key="bar.month" class="flex flex-1 flex-col items-center justify-end gap-3">
                    <div
                      class="w-full rounded-full"
                      :class="bar.active ? 'bg-[#dbe88d]' : 'bg-slate-200/80'"
                      :style="{ height: bar.height }"
                    ></div>
                    <span class="text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-300">{{ bar.month }}</span>
                  </div>
                </div>
              </article>
            </div>

            <div class="grid gap-5 md:grid-cols-[1fr_1.2fr]">
              <article class="rounded-[2.5rem] border border-white/70 bg-white/92 p-6 shadow-[0_18px_50px_rgba(98,120,92,0.12)] backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-base font-extrabold">12 Days left</p>
                    <p class="text-xs text-slate-400">Until the university anniversary gala.</p>
                  </div>
                  <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f4f7ea] text-2xl text-[#95a574]">
                    ◌
                  </div>
                </div>
              </article>

              <article class="rounded-[2.5rem] bg-slate-900 p-6 text-white shadow-[0_22px_60px_rgba(16,18,17,0.28)]">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-base font-extrabold">Top Teachers</p>
                    <p class="text-xs text-white/55">Most active this year</p>
                  </div>
                  <span class="rounded-full bg-[#dbe88d] px-3 py-1 text-[10px] font-black uppercase tracking-[0.28em] text-slate-900">2026</span>
                </div>

                <div class="mt-6 space-y-4">
                  <div v-for="teacher in teachers" :key="teacher.name" class="flex items-center justify-between rounded-2xl border border-white/8 bg-white/5 px-4 py-3">
                    <div class="flex items-center gap-4">
                      <img class="h-11 w-11 rounded-2xl object-cover ring-1 ring-white/10" :src="teacher.avatar" :alt="teacher.name" />
                      <div>
                        <p class="text-sm font-bold">{{ teacher.name }}</p>
                        <p class="text-xs text-white/55">{{ teacher.role }}</p>
                      </div>
                    </div>
                    <span class="text-white/45">↗</span>
                  </div>
                </div>
              </article>

              <article class="rounded-[2.5rem] border border-white/70 bg-white/92 p-6 shadow-[0_18px_50px_rgba(98,120,92,0.12)] backdrop-blur-sm md:col-span-2">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-base font-extrabold">Success Rate</p>
                    <p class="text-xs text-slate-400">Academic completion progress</p>
                  </div>
                  <button class="rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-500" type="button">
                    Overview
                  </button>
                </div>
                <div class="mt-6 flex items-center gap-6">
                  <div class="relative flex h-36 w-36 items-center justify-center rounded-full" :style="{ background: `conic-gradient(#f0cc7d 0 78%, #f5f0e4 78% 100%)` }">
                    <div class="flex h-[74%] w-[74%] items-center justify-center rounded-full bg-white">
                      <div class="text-center">
                        <div class="text-3xl font-extrabold tracking-tight">78%</div>
                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.35em] text-slate-400">Completed</div>
                      </div>
                    </div>
                  </div>
                  <div class="grid gap-3 text-sm text-slate-500 sm:grid-cols-2">
                    <div class="rounded-2xl bg-[#f8f9f3] px-4 py-3">
                      <div class="text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-400">Courses</div>
                      <div class="mt-2 text-2xl font-extrabold text-slate-900">42</div>
                    </div>
                    <div class="rounded-2xl bg-[#f8f9f3] px-4 py-3">
                      <div class="text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-400">Attendance</div>
                      <div class="mt-2 text-2xl font-extrabold text-slate-900">96%</div>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          </div>

          <article class="rounded-[2.75rem] border border-white/70 bg-white/92 p-6 shadow-[0_18px_50px_rgba(98,120,92,0.12)] backdrop-blur-sm xl:min-h-full">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-2xl font-extrabold tracking-tight">Upcoming Events/Exams</p>
                <p class="mt-1 text-xs text-slate-400">Scheduled for the term and important tests.</p>
              </div>
              <button class="rounded-full bg-slate-900 px-4 py-2 text-[11px] font-black uppercase tracking-[0.28em] text-white" type="button">
                View All
              </button>
            </div>

            <div class="mt-6 space-y-3">
              <article
                v-for="event in events"
                :key="event.title"
                class="rounded-[1.6rem] border border-slate-100 bg-white px-4 py-4 shadow-[0_8px_24px_rgba(81,92,79,0.06)] transition hover:border-[#dbe88d] hover:bg-[#f8faef]"
              >
                <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#9fb08d]">{{ event.date }}</p>
                <h3 class="mt-2 text-base font-bold leading-snug text-slate-900">{{ event.title }}</h3>
              </article>
            </div>
          </article>
        </section>
      </main>
    </div>
  </div>
</template>
