@extends('layouts.app')

@section('content')
<div class="flex flex-col xl:flex-row gap-6 xl:gap-4 w-full relative">
    <!-- Left / Replay Player -->
    <div class="relative flex-1 flex flex-col justify-center overflow-hidden 
                bg-gray-800 dark:bg-gray-900 rounded-2xl py-6 px-4 z-10 shadow-lg">
        
        <!-- Floating Title -->
        <div class="absolute top-6 left-1/2 transform -translate-x-1/2 
                    bg-gray-900 dark:bg-gray-800 px-4 py-1 rounded-full 
                    shadow-md text-white text-lg font-semibold text-center z-50">
            {{ $mapName }} - {{ $categoryName }}
        </div>

        <div class="not-prose relative rounded-xl overflow-hidden bg-gray-900 mt-10">
            <div class="relative rounded-xl overflow-auto no-scrollbar">
                <div class="block max-h-[600px] my-4 rounded-xl border border-gray-600">
                    <!-- Player Here -->
                    <div id="hlv-target" class="h-[600px] rounded-xl"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right / Controls -->
    <div class="flex flex-col items-center justify-start 
                bg-gray-900 dark:bg-gray-900 rounded-2xl 
                py-6 px-6 w-full xl:w-1/6 shadow-lg">
        <button id="downloadBtn" 
                class="w-full py-2 px-4 font-semibold text-md text-white 
                       bg-red-600 rounded-lg hover:bg-white hover:text-red-600 
                       transition-colors duration-300 shadow-md">
            Download
        </button>    
        <!-- Optional extra controls -->
    </div>
</div>

<script src="{{ asset('js/hlviewer.min.js') }}"></script>

<script>
    window.addEventListener('load', async () => {
        // Function to fetch data from the server
        const fetchData = async (url, data) => {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                return await response.json();
            } catch (error) {
                console.error('Fetch error:', error);
            }
        };

        // Define URLs
        const urlBhop = "http://nostalgia.neopanel.ro/fdbd8f4f-7e2e-4a02-93b2-d37b4c94836e/cstrike/";
        const urlDr = "http://nostalgia.neopanel.ro/d595f794-34d2-45b2-a961-779858c7350e/cstrike/";
        let resourceUrl = '';
        let mapName = '{{ $mapName }}';
        let categoryName = '{{ $categoryName }}';

        // Determine the resource URL based on the map name
        resourceUrl = mapName.includes("deathrun") ? urlDr : urlBhop;

        // Define resource paths
        const paths = {
            base: `/proxy?url=${resourceUrl}`,
            replays: '/proxy?url=https://cs-gfx.eu/uploads/recording',
            maps: `/proxy?url=${resourceUrl}maps`,
            wads: `/proxy?url=${resourceUrl}`,
            skies: `/proxy?url=${resourceUrl}gfx/env`,
            sounds: `/proxy?url=${resourceUrl}sound`,
        };

        // Initialize and load the viewer
        const viewer = HLViewer.init('#hlv-target', { paths });
        await viewer.load(`${mapName}.bsp`);
        await viewer.load(`${mapName}/[${categoryName}].rec`);

	document.getElementById("downloadBtn").addEventListener("click", () => {
    		// Create a link element
   		 const link = document.createElement("a");

    		// Construct the file URL
    		const fileURL = `https://cs-gfx.eu/uploads/recording/${mapName}/[${categoryName}].rec`;

    		// Configure the link
    		link.href = fileURL;
    		link.download = `${mapName} - [${categoryName}].rec`; // The file name for the download

    		// Trigger the download
    		link.click();

    		// Optionally remove the link element after triggering the download
    		link.remove();
	});
});	

    // Utility function to get URL parameters
    function getUrlParams() {
        return Object.fromEntries(new URLSearchParams(window.location.search).entries());
    }
</script>


@endsection