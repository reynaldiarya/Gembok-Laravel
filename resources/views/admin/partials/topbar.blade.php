<!-- Top Bar -->
<div class="sticky top-0 z-40 bg-white shadow-md">
    <div class="flex items-center justify-between h-16 px-6">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600 hover:text-gray-900">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <!-- Page Title (optional) -->
        <div class="hidden lg:block">
            <h1 class="text-lg font-semibold text-gray-800">
                @yield('page-title', 'Dashboard')
            </h1>
        </div>
        
        <!-- Right Side -->
        <div class="flex items-center space-x-4 ml-auto">
            <!-- Language Switcher -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-1 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 border rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-globe"></i>
                    <span>{{ app()->getLocale() == 'id' ? 'ID' : 'EN' }}</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-lg border z-50">
                    <a href="{{ route('language.switch', 'en') }}" class="flex items-center px-4 py-2 text-sm hover:bg-gray-50 {{ app()->getLocale() == 'en' ? 'text-cyan-600 font-medium' : 'text-gray-700' }}">
                        <span class="mr-2">🇺🇸</span> English
                    </a>
                    <a href="{{ route('language.switch', 'id') }}" class="flex items-center px-4 py-2 text-sm hover:bg-gray-50 {{ app()->getLocale() == 'id' ? 'text-cyan-600 font-medium' : 'text-gray-700' }}">
                        <span class="mr-2">🇮🇩</span> Indonesia
                    </a>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-3 hover:bg-gray-50 rounded-lg px-3 py-2 transition">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold shadow">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                </button>
                
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border z-50">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b">
                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="py-1">
                        <a href="{{ route('admin.change-password') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-key w-5 mr-3 text-gray-400"></i>
                            Ganti Password
                        </a>
                        <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-cog w-5 mr-3 text-gray-400"></i>
                            Settings
                        </a>
                    </div>
                    
                    <!-- Logout -->
                    <div class="border-t py-1">
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
