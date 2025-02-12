<template>
    <div class="flex flex-col h-full">
        <AlertDialog :open="!!error">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Error</AlertDialogTitle>
                    <AlertDialogDescription>{{ error }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <Button variant="outline" @click="error = null"
                        >Dismiss</Button
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <ScrollArea class="flex-1 min-h-0" ref="scrollArea">
            <div class="px-4">
                <div class="py-4 space-y-4" ref="messagesContainer">
                    <div
                        v-for="message in localMessages"
                        :key="message.id"
                        class="flex items-start"
                        :class="
                            message.role === 'assistant'
                                ? 'justify-start'
                                : 'justify-end'
                        "
                    >
                        <div
                            v-if="message.role === 'assistant'"
                            class="flex items-center justify-center p-2 mt-2 mr-2 rounded-full bg-primary aspect-square"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-robot"
                                class=""
                            />
                        </div>
                        <Card
                            :class="[
                                'max-w-[80%]',
                                message.role === 'assistant'
                                    ? 'bg-muted'
                                    : 'bg-primary',
                            ]"
                        >
                            <CardContent class="relative p-4">
                                <div
                                    v-if="message.isLoading"
                                    class="flex items-center gap-2"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-circle-notch"
                                        class="w-4 h-4 animate-spin"
                                    />
                                    <span>AI is thinking...</span>
                                </div>
                                <template v-else>
                                    <div
                                        v-if="message.role === 'assistant'"
                                        class="prose prose-invert prose-pre:bg-background prose-pre:border prose-pre:border-border max-w-none"
                                    >
                                        <span
                                            v-html="
                                                renderMarkdown(message.content)
                                            "
                                        ></span>
                                    </div>
                                    <div v-else class="text-primary-foreground">
                                        {{ message.content }}
                                    </div>
                                </template>
                                <div
                                    class="flex items-center justify-between gap-4"
                                    :class="
                                        message.role === 'assistant'
                                            ? 'text-gray-400'
                                            : 'text-purple-200'
                                    "
                                >
                                    <div class="text-xs">
                                        {{ formatDateTime(message.updated_at) }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            v-if="copiedStates.get(message.id)"
                                            class="text-xs"
                                        >
                                            Copié
                                        </div>
                                        <div
                                            class="cursor-pointer"
                                            @click="
                                                handleCopy(
                                                    message.content,
                                                    message.id
                                                )
                                            "
                                        >
                                            <font-awesome-icon
                                                :icon="
                                                    copiedStates.get(message.id)
                                                        ? 'fa-solid fa-clipboard-check'
                                                        : 'fa-solid fa-clipboard'
                                                "
                                                class="w-4 h-4"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <div
                            v-if="message.role === 'user'"
                            class="flex items-center justify-center p-2 mt-2 ml-2 border rounded-full bg-muted aspect-square"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-user"
                                class=""
                            />
                        </div>
                    </div>
                </div>
            </div>
        </ScrollArea>

        <Button
            v-show="showScrollButton"
            @click="scrollToBottom"
            size="icon"
            variant="outline"
            class="fixed z-20 rounded-full shadow-md bottom-24 right-6"
        >
            <font-awesome-icon icon="fa-solid fa-arrow-down" class="w-4 h-4" />
        </Button>

        <div
            class="shrink-0 p-4 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 relative z-10 shadow-[0_-1px_3px_rgba(0,0,0,0.1)]"
        >
            <form @submit.prevent="sendMessage" class="flex space-x-2">
                <Input
                    v-model="newMessage"
                    type="text"
                    class="flex-1"
                    placeholder="Type your message..."
                    :disabled="streaming"
                />
                <Button
                    type="submit"
                    :disabled="!newMessage.trim() || streaming"
                >
                    <font-awesome-icon
                        v-if="!streaming"
                        icon="fa-solid fa-paper-plane"
                        class="w-4 h-4"
                    />
                    <font-awesome-icon
                        v-else
                        icon="fa-solid fa-spinner"
                        class="w-4 h-4 animate-spin"
                    />
                </Button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import axios from "axios";
import { renderMarkdown, copyToClipboard, formatDateTime } from "@/lib/utils";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";

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
const scrollArea = ref(null);
const messagesContainer = ref(null);
const error = ref(null);
const localMessages = ref([...props.conversation.messages]);
const channelSubscription = ref(null);
const showScrollButton = ref(false);
const isNearBottom = ref(true);
const copiedStates = ref(new Map());

const scrollToBottom = () => {
    if (scrollArea.value) {
        const viewport = scrollArea.value.$el.querySelector(
            "[data-radix-scroll-area-viewport]"
        );
        if (viewport) {
            viewport.scrollTo({
                top: viewport.scrollHeight,
                behavior: "smooth",
            });
        }
    }
};

// Reset the scroll-to-bottom button state when landing at the bottom
const handleScroll = () => {
    const viewport = scrollArea.value.$el.querySelector(
        "[data-radix-scroll-area-viewport]"
    );
    if (viewport) {
        const scrollBottom =
            viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight;
        isNearBottom.value = scrollBottom < 100;
        showScrollButton.value = !isNearBottom.value;
    }
};

watch(
    () => props.conversation.messages,
    () => {
        if (isNearBottom.value) {
            scrollToBottom();
        }
    },
    { deep: true }
);

watch(
    () => props.conversation,
    () => {
        error.value = null;
        scrollToBottom();
        isNearBottom.value = true;
        showScrollButton.value = false;
    },
    { deep: true }
);

const handleCopy = async (text, messageId) => {
    const success = await copyToClipboard(text);
    if (success) {
        copiedStates.value.set(messageId, true);
        setTimeout(() => {
            copiedStates.value.delete(messageId);
        }, 2000);
    } else {
        alert("Unable to copy text to clipboard");
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || streaming.value) return;

    const messageContent = newMessage.value;
    newMessage.value = "";
    streaming.value = true;
    error.value = null;

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

    await nextTick();
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
        await nextTick();
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
            console.log("📨 Message reçu:", event);

            // Only set error if it comes from an event with an error property
            if (event.error) {
                console.error("❌ Error received:", event.error);
                error.value = event.error;
                streaming.value = false;
                localMessages.value = localMessages.value.filter(
                    (msg) => !msg.isLoading
                );
                return;
            }

            const lastMessage =
                localMessages.value[localMessages.value.length - 1];

            if (!lastMessage || lastMessage.role !== "assistant") {
                console.log("⚠️ No assistant message to update");
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

// Reset error when conversation changes
watch(
    () => props.conversation,
    () => {
        error.value = null;
    },
    { deep: true }
);

watch(
    () => localMessages.value,
    async () => {
        if (isNearBottom.value) {
            await nextTick();
            scrollToBottom();
        }
    },
    { deep: true }
);

watch(
    () => props.conversation,
    async (newConv) => {
        error.value = null;
        localMessages.value = [...newConv.messages];
        await nextTick();
        scrollToBottom();
        isNearBottom.value = true;
        showScrollButton.value = false;
    },
    { immediate: true, deep: true }
);

onMounted(async () => {
    setupChannelSubscription();
    await nextTick();

    // Add scroll event listener to the viewport
    if (scrollArea.value) {
        const viewport = scrollArea.value.$el.querySelector(
            "[data-radix-scroll-area-viewport]"
        );
        if (viewport) {
            viewport.addEventListener("scroll", handleScroll);
            scrollToBottom();
        }
    }
});

onBeforeUnmount(() => {
    if (channelSubscription.value) {
        const channel = `chat.${props.conversation.id}`;
        window.Echo.leave(channel);
    }

    // Remove scroll event listener
    if (scrollArea.value) {
        const viewport = scrollArea.value.$el.querySelector(
            "[data-radix-scroll-area-viewport]"
        );
        if (viewport) {
            viewport.removeEventListener("scroll", handleScroll);
        }
    }
});
</script>

<style lang="scss" scoped></style>
