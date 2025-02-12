<template>
    <div class="flex flex-col h-full">
        <!-- Error Alert -->
        <div
            v-if="error"
            class="p-4 m-4 text-red-500 border border-red-500 rounded-lg bg-red-500/10"
        >
            <div class="flex items-center gap-2">
                <font-awesome-icon icon="fa-solid fa-circle-exclamation" />
                <span>{{ error }}</span>
            </div>
            <button
                @click="error = ''"
                class="mt-1 text-xs text-red-400 hover:text-red-300"
            >
                Dismiss
            </button>
        </div>

        <!-- Messages Area -->
        <div
            class="flex-1 p-4 space-y-4 overflow-y-auto"
            ref="messagesContainer"
        >
            <div
                v-for="message in localMessages"
                :key="message.id"
                class="flex"
                :class="
                    message.role === 'assistant'
                        ? 'justify-start'
                        : 'justify-end'
                "
            >
                <div
                    class="max-w-[80%] rounded-lg p-3"
                    :class="
                        message.role === 'assistant'
                            ? 'bg-gray-700 text-white'
                            : 'bg-blue-600 text-white'
                    "
                >
                    <template v-if="message.isLoading">
                        <div class="flex items-center gap-2">
                            <font-awesome-icon
                                icon="fa-solid fa-circle-notch"
                                class="animate-spin"
                            />
                            <span>AI is thinking...</span>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            v-if="message.role === 'assistant'"
                            class="prose prose-invert prose-pre:bg-gray-800 prose-pre:border prose-pre:border-gray-600 max-w-none"
                            v-html="renderMarkdown(message.content)"
                        />
                        <div v-else>
                            {{ message.content }}
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 border-t border-gray-700">
            <form @submit.prevent="sendMessage" class="flex space-x-2">
                <input
                    v-model="newMessage"
                    type="text"
                    class="flex-1 px-4 py-2 text-white bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Type your message..."
                    :disabled="streaming"
                />
                <button
                    type="submit"
                    class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    :disabled="!newMessage.trim() || streaming"
                >
                    <font-awesome-icon
                        v-if="!streaming"
                        icon="fa-solid fa-paper-plane"
                    />
                    <font-awesome-icon
                        v-else
                        icon="fa-solid fa-spinner"
                        class="animate-spin"
                    />
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import axios from "axios";
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import "highlight.js/styles/github-dark.css";

const md = new MarkdownIt({
    html: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return ""; // use external default escaping
    },
});

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    model: {
        type: String,
        required: true,
    },
});

const newMessage = ref("");
const streaming = ref(false);
const streamedContent = ref("");
const messagesContainer = ref(null);
const error = ref("");
const localMessages = ref([...props.conversation.messages]);
const channelSubscription = ref(null);

const scrollToBottom = () => {
    if (messagesContainer.value) {
        setTimeout(() => {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight;
        }, 50);
    }
};

watch(() => props.conversation.messages, scrollToBottom, { deep: true });

const sendMessage = async () => {
    if (!newMessage.value.trim() || streaming.value) return;

    const messageContent = newMessage.value;
    newMessage.value = "";
    streaming.value = true;
    error.value = "";

    // Add user message immediately to local messages
    localMessages.value.push({
        id: `temp-${Date.now()}`,
        role: "user",
        content: messageContent,
    });

    // Add a placeholder for assistant's response
    const assistantMessageId = `temp-assistant-${Date.now()}`;
    localMessages.value.push({
        id: assistantMessageId,
        role: "assistant",
        content: "",
        isLoading: true,
    });

    scrollToBottom();

    try {
        await axios.post(route("conversations.ask", props.conversation.id), {
            message: messageContent,
            model: props.model,
        });
    } catch (err) {
        console.error("Error sending message:", err);
        error.value =
            err.response?.data?.message ||
            "Failed to send message. Please try again.";
        streaming.value = false;
        // Remove the loading message on error
        localMessages.value = localMessages.value.filter(
            (msg) => msg.id !== assistantMessageId
        );
        scrollToBottom();
    }
};

const setupChannelSubscription = () => {
    const channel = `chat.${props.conversation.id}`;
    console.log("🔌 Tentative de connexion au canal:", channel);

    channelSubscription.value = window.Echo.private(channel)
        .subscribed(() => {
            console.log("✅ Connecté avec succès au canal:", channel);
        })
        .error((error) => {
            console.error("❌ Erreur de connexion au canal:", error);
        })
        .listen(".message.streamed", (event) => {
            // Updated to use short name since we defined the namespace
            console.log("📨 Message reçu:", event);

            const lastMessage =
                localMessages.value[localMessages.value.length - 1];

            if (!lastMessage || lastMessage.role !== "assistant") {
                console.log("⚠️ No assistant message to update");
                return;
            }

            if (event.error) {
                console.error("❌ Error received:", event.error);
                error.value = event.error;
                streaming.value = false;
                localMessages.value = localMessages.value.filter(
                    (msg) => !msg.isLoading
                );
                return;
            }

            if (lastMessage.isLoading) {
                lastMessage.isLoading = false;
                streaming.value = true;
            }

            lastMessage.content = event.content;
            scrollToBottom();

            if (event.isComplete) {
                console.log("✅ Message complete");
                streaming.value = false;
            }
        });
};

const renderMarkdown = (content) => {
    return md.render(content || "");
};

onMounted(() => {
    setupChannelSubscription();
    scrollToBottom();
});

onBeforeUnmount(() => {
    if (channelSubscription.value) {
        const channel = `chat.${props.conversation.id}`;
        window.Echo.leave(channel);
    }
});
</script>

<style lang="scss" scoped></style>
