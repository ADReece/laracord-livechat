{{-- Laracord Live Chat Widget Include --}}
<div id="laracord-chat-container">
    {{-- Chat Toggle Button --}}
    <button id="laracord-chat-toggle" style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        💬
    </button>
</div>

{{-- Load chat widget in iframe for isolation --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('laracord-chat-toggle');
    let chatIframe = null;

    toggle.addEventListener('click', function() {
        if (!chatIframe) {
            // Create iframe
            chatIframe = document.createElement('iframe');
            chatIframe.src = '{{ url("laracord-chat/widget") }}';
            chatIframe.style.cssText = `
                position: fixed;
                bottom: 90px;
                right: 20px;
                width: 350px;
                height: 500px;
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                background: white;
            `;
            
            // Mobile responsive
            if (window.innerWidth <= 480) {
                chatIframe.style.width = 'calc(100vw - 40px)';
                chatIframe.style.height = 'calc(100vh - 120px)';
                chatIframe.style.bottom = '90px';
                chatIframe.style.right = '20px';
            }
            
            document.body.appendChild(chatIframe);
            
            // Hide toggle button
            toggle.style.display = 'none';
            
            // Listen for messages from iframe to handle close
            window.addEventListener('message', function(event) {
                if (event.data === 'closeLaracordChat') {
                    if (chatIframe) {
                        document.body.removeChild(chatIframe);
                        chatIframe = null;
                        toggle.style.display = 'flex';
                    }
                }
            });
        }
    });
});
</script>
