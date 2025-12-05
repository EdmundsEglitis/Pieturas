<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-red-600">Import Bus Stops from Excel</h2>
    </x-slot>

    <div class="p-6">
        @if(session('success'))
            <div class="p-4 mb-4 bg-green-200 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.busstop.importExcel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <p class="mb-4">Upload your Excel file (.xlsx, .xls, or .csv) to import bus stops.</p>

            <input type="file" name="file" required class="mb-4">

            <button type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                Import Excel
            </button>
        </form>
    </div>
</x-app-layout>
