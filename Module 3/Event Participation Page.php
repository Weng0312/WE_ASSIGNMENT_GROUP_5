<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Event Participant List - FK Student Club &amp; Event System</title>
<!-- BEGIN: External Scripts -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- END: External Scripts -->
<style data-purpose="custom-layout">
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }

    .sidebar-active {
      background-color: #2563eb;
      color: white;
    }

    .status-confirmed {
      background-color: #dcfce7;
      color: #166534;
    }

    .status-waiting {
      background-color: #fef3c7;
      color: #92400e;
    }
  </style>
<script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              light: '#eff6ff',
              DEFAULT: '#1e40af',
              dark: '#1e3a8a',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen flex">
<!-- BEGIN: Sidebar Navigation -->
<aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0 fixed h-full z-10" data-purpose="sidebar">
<div class="p-6">
<div class="flex items-center gap-3">
<img alt="Logo Placeholder" class="w-10 h-10 object-contain hidden" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAm-NzY8qYe0033AIUFOgcsCU09Dgj4g2079pLxuIJaRphO0pVQQ0YPjrYepcultAaItIhkyOhtEwuZLMsocC87ojaFgm6wiQURluxJ0rmfKSclUIEm7kA_buptKu76mv5CwSK56VIB70iEHvI3qHG5uy1VpaKjfplwAkj-2-2spzS_-4zJNc2gWIgyD-B7XztVtEM0MnpGeACI85YW0EgTz1CXGWpNdICoTFLGwUhf2FPrzdCd9eYOKd0niQVeJLOX0eRhmdnsQg8"/>
<div class="bg-brand-dark w-10 h-10 rounded-md flex items-center justify-center text-white font-bold text-xs text-center">
          UMP<br/>Logo
        </div>
<h1 class="text-brand-dark font-bold text-lg leading-tight">FK Student Club &amp; Event System</h1>
</div>
</div>
<nav class="mt-6 px-3 space-y-1">
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Dashboard</span>
</a>
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Event Management</span>
</a>
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Create/Edit Event</span>
</a>
<a class="flex items-center px-4 py-3 sidebar-active rounded-lg transition-colors group shadow-md shadow-blue-200" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span class="font-medium">Event Participant List</span>
</a>
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Attendance Management</span>
</a>
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Attendance Monitoring</span>
</a>
<a class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors group" href="#">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>Event Reports</span>
</a>
</nav>
</aside>
<!-- END: Sidebar Navigation -->
<!-- BEGIN: Main Content Area -->
<main class="flex-1 ml-64 p-8 overflow-y-auto" data-purpose="main-content">
<!-- BEGIN: Header -->
<header class="flex justify-between items-center mb-8" data-purpose="page-header">
<div>
<h2 class="text-3xl font-bold text-gray-800">Event Participant List Page</h2>
<p class="text-gray-500 mt-1">View registered students and their registration status.</p>
</div>
<div class="flex items-center gap-4 bg-white p-2 pr-6 rounded-full shadow-sm border border-gray-100">
<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
<svg class="w-6 h-6 text-blue-500" fill="currentColor" viewbox="0 0 20 20"><path clip-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" fill-rule="evenodd"></path></svg>
</div>
<div class="flex flex-col">
<span class="text-sm font-semibold text-gray-700">Siti Aisyah</span>
<span class="text-xs text-gray-400">Committee Member</span>
</div>
<svg class="w-4 h-4 text-gray-400 ml-2" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
</header>
<!-- END: Header -->
<!-- BEGIN: Event Basic Info Card -->
<section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 flex flex-wrap gap-12 items-center" data-purpose="event-overview">
<div class="flex items-center gap-4 border-r border-gray-100 pr-12">
<div class="w-14 h-14 bg-brand-light rounded-xl flex items-center justify-center text-brand">
<svg class="w-8 h-8" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
<div>
<h3 class="text-xl font-bold text-gray-800">Tech Talk 2026</h3>
<div class="flex items-center gap-2 mt-1 text-gray-500 text-sm">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span>May 20, 2026 (Wed)</span>
</div>
</div>
</div>
<div class="flex flex-col gap-1">
<span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Venue</span>
<div class="flex items-center gap-2 text-gray-700">
<svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span class="font-medium">Auditorium, Main Building</span>
</div>
</div>
<div class="flex flex-col gap-1">
<span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Capacity</span>
<div class="flex items-center gap-2 text-gray-700">
<svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span class="font-medium">150 Seats</span>
</div>
</div>
<div class="flex flex-col gap-1">
<span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Current Registrations</span>
<div class="flex items-center gap-2 text-gray-700">
<svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
<span class="font-medium">112 Registered</span>
</div>
</div>
</section>
<!-- END: Event Basic Info Card -->
<!-- BEGIN: Quick Stats Cards -->
<section class="grid grid-cols-4 gap-6 mb-8" data-purpose="stats-grid">
<!-- Total Registered -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
<div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
<div>
<p class="text-xs font-semibold text-gray-400 uppercase tracking-tight">Total Registered</p>
<p class="text-2xl font-bold text-brand">112</p>
<p class="text-xs text-gray-500">students</p>
</div>
</div>
<!-- Confirmed -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
<div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
<div>
<p class="text-xs font-semibold text-gray-400 uppercase tracking-tight">Confirmed</p>
<p class="text-2xl font-bold text-green-600">78</p>
<p class="text-xs text-gray-500">students</p>
</div>
</div>
<!-- Waiting List -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
<div class="w-12 h-12 bg-orange-50 rounded-full flex items-center justify-center text-orange-500">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
<div>
<p class="text-xs font-semibold text-gray-400 uppercase tracking-tight">Waiting List</p>
<p class="text-2xl font-bold text-orange-500">34</p>
<p class="text-xs text-gray-500">students</p>
</div>
</div>
<!-- Remaining Seats -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
<div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center text-purple-600">
<svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M20 12H4M12 4v16m-8-8a8 8 0 1116 0 8 8 0 01-16 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</div>
<div>
<p class="text-xs font-semibold text-gray-400 uppercase tracking-tight">Remaining Seats</p>
<p class="text-2xl font-bold text-indigo-800">38</p>
<p class="text-xs text-gray-500">seats</p>
</div>
</div>
</section>
<!-- END: Quick Stats Cards -->
<!-- BEGIN: Data Table Controls -->
<div class="bg-white border border-gray-200 rounded-t-xl p-4 flex flex-wrap items-center justify-between gap-4" data-purpose="table-filters">
<div class="relative w-96">
<span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
<svg class="h-5 w-5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</span>
<input class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Search by student name or ID..." type="text"/>
</div>
<div class="flex items-center gap-3">
<select class="border border-gray-200 rounded-lg text-sm py-2 px-4 focus:ring-blue-500 focus:border-blue-500 min-w-[160px]">
<option>All Statuses</option>
<option>Confirmed</option>
<option>Waiting</option>
</select>
</div>
</div>
<!-- END: Data Table Controls -->
<!-- BEGIN: Participant Table -->
<div class="bg-white border-x border-b border-gray-200 rounded-b-xl overflow-hidden" data-purpose="participant-table-container">
<table class="w-full text-left text-sm" id="participant-table">
<thead class="bg-gray-50 text-gray-500 font-semibold uppercase tracking-wider text-xs border-b border-gray-200">
<tr>
<th class="px-6 py-4">#</th>
<th class="px-6 py-4">Student ID</th>
<th class="px-6 py-4">Name</th>
<th class="px-6 py-4">Email</th>
<th class="px-6 py-4">Programme</th>
<th class="px-6 py-4">Status</th>
<th class="px-6 py-4">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-100">
<!-- Row 1 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">1</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230123</td>
<td class="px-6 py-4 text-gray-700">Ayesha Khan</td>
<td class="px-6 py-4 text-blue-600">ayesha.khan@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Computer Science</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 2 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">2</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230157</td>
<td class="px-6 py-4 text-gray-700">Bilal Ahmed</td>
<td class="px-6 py-4 text-blue-600">bilal.ahmed@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Software Engineering</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 3 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">3</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230211</td>
<td class="px-6 py-4 text-gray-700">Hira Malik</td>
<td class="px-6 py-4 text-blue-600">hira.malik@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Information Technology</td>
<td class="px-6 py-4">
<span class="status-waiting px-3 py-1 rounded-full text-xs font-semibold">Waiting</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 4 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">4</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230098</td>
<td class="px-6 py-4 text-gray-700">Usman Ali</td>
<td class="px-6 py-4 text-blue-600">usman.ali@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Cyber Security</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 5 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">5</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230145</td>
<td class="px-6 py-4 text-gray-700">Sara Hashmi</td>
<td class="px-6 py-4 text-blue-600">sara.hashmi@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Data Science</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 6 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">6</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230233</td>
<td class="px-6 py-4 text-gray-700">Hamza Raza</td>
<td class="px-6 py-4 text-blue-600">hamza.raza@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Computer Science</td>
<td class="px-6 py-4">
<span class="status-waiting px-3 py-1 rounded-full text-xs font-semibold">Waiting</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 7 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">7</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230108</td>
<td class="px-6 py-4 text-gray-700">Fatima Noor</td>
<td class="px-6 py-4 text-blue-600">fatima.noor@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Software Engineering</td>
<td class="px-6 py-4">
<span class="status-waiting px-3 py-1 rounded-full text-xs font-semibold">Waiting</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 8 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">8</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230176</td>
<td class="px-6 py-4 text-gray-700">Ali Hassan</td>
<td class="px-6 py-4 text-blue-600">ali.hassan@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Information Technology</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 9 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">9</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230205</td>
<td class="px-6 py-4 text-gray-700">Zainab Tariq</td>
<td class="px-6 py-4 text-blue-600">zainab.tariq@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Data Science</td>
<td class="px-6 py-4">
<span class="status-waiting px-3 py-1 rounded-full text-xs font-semibold">Waiting</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
<!-- Row 10 -->
<tr class="hover:bg-gray-50 transition-colors">
<td class="px-6 py-4 text-gray-500">10</td>
<td class="px-6 py-4 font-medium text-gray-700">FK20230112</td>
<td class="px-6 py-4 text-gray-700">Muhammad Ahmed</td>
<td class="px-6 py-4 text-blue-600">m.ahmed@fk.edu.pk</td>
<td class="px-6 py-4 text-gray-600">BS Cyber Security</td>
<td class="px-6 py-4">
<span class="status-confirmed px-3 py-1 rounded-full text-xs font-semibold">Confirmed</span>
</td>
<td class="px-6 py-4 flex gap-2">
<button class="flex items-center gap-1 border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                View
              </button>
<button class="flex items-center gap-1 border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50 text-xs font-medium">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Remove
              </button>
</td>
</tr>
</tbody>
</table>
<!-- BEGIN: Pagination -->
<div class="px-6 py-4 bg-gray-50 flex items-center justify-between" data-purpose="table-pagination">
<span class="text-xs text-gray-500">Showing 1 to 10 of 112 participants</span>
<div class="flex items-center gap-2">
<button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-md bg-white text-gray-400 hover:bg-gray-100 transition-colors">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</button>
<button class="w-8 h-8 flex items-center justify-center rounded-md bg-blue-600 text-white text-xs font-semibold">1</button>
<button class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-gray-200 text-gray-600 text-xs font-medium hover:bg-gray-50">2</button>
<button class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-gray-200 text-gray-600 text-xs font-medium hover:bg-gray-50">3</button>
<span class="text-gray-400 px-1">...</span>
<button class="w-8 h-8 flex items-center justify-center rounded-md bg-white border border-gray-200 text-gray-600 text-xs font-medium hover:bg-gray-50">12</button>
<button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-md bg-white text-gray-400 hover:bg-gray-100 transition-colors">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewbox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
</button>
</div>
</div>
<!-- END: Pagination -->
</div>
<!-- END: Participant Table -->
</main>
<!-- END: Main Content Area -->
</body></html>