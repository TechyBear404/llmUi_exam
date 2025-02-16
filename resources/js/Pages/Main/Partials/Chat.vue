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
                <div class="py-4 m-auto space-y-4" ref="messagesContainer">
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
                                    ? 'bg-muted rounded-bl-none'
                                    : 'bg-primary rounded-br-none',
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
                                    class="flex items-center justify-between gap-4 mt-4"
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
            class="fixed z-20 rounded-full shadow-md bottom-32 right-6"
        >
            <font-awesome-icon icon="fa-solid fa-arrow-down" class="w-4 h-4" />
        </Button>

        <div
            class="shrink-0 p-4 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 relative z-10 shadow-[0_-1px_3px_rgba(0,0,0,0.1)]"
        >
            <form @submit.prevent="sendMessage" class="flex space-x-2">
                <Textarea
                    v-model="newMessage"
                    class="flex-1"
                    placeholder="Type your message..."
                    :disabled="streaming"
                    @keydown="handleKeyDown"
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
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { Card, CardContent } from "@/Components/ui/card";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";
import "highlight.js/styles/github.css";
import { router, useForm } from "@inertiajs/vue3";

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

const emit = defineEmits(["update:conversation"]);

const newMessage = ref("");
const streaming = ref(false);
// const streamedContent = ref("");
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
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
    });

    // Add a placeholder for assistant's response
    const assistantMessageId = `temp-assistant-${Date.now()}`;
    const tempAssistantMessage = {
        id: assistantMessageId,
        role: "assistant",
        content: "",
        isLoading: true,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
    };

    localMessages.value.push(tempAssistantMessage);
    await nextTick();
    scrollToBottom();

    try {
        // Wait for channel subscription to be ready
        await new Promise((resolve) => {
            setupChannelSubscription(() => resolve());
        });

        // Now send the message
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

const setupChannelSubscription = (onSubscribed = null) => {
    const channel = `chat.${props.conversation.id}`;
    console.log("🔌 Connecting to channel:", channel);

    // Cleanup existing subscription if any
    if (channelSubscription.value) {
        window.Echo.leave(channel);
    }

    channelSubscription.value = window.Echo.private(channel)
        .subscribed(() => {
            console.log("✅ Successfully subscribed to channel:", channel);
            if (onSubscribed) onSubscribed();
        })
        .error((error) => {
            console.error("❌ Channel subscription error:", error);
            streaming.value = false;
            error.value = "Failed to connect to chat. Please refresh the page.";
        })
        .listen(".message.streamed", (event) => {
            console.log("📨 Message received:", event);

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

            lastMessage.isLoading = false;
            lastMessage.content = event.content;

            if (event.isComplete) {
                console.log("✅ Message complete");
                streaming.value = false;
                updateTitle();
            }

            if (isNearBottom.value) {
                nextTick(() => scrollToBottom());
            }
        });
};

// Setup initial channel subscription
onMounted(() => {
    setupChannelSubscription();

    // Add scroll event listener
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

// Cleanup subscription on unmount
onBeforeUnmount(() => {
    if (channelSubscription.value) {
        window.Echo.leave(`chat.${props.conversation.id}`);
    }

    if (scrollArea.value) {
        const viewport = scrollArea.value.$el.querySelector(
            "[data-radix-scroll-area-viewport]"
        );
        if (viewport) {
            viewport.removeEventListener("scroll", handleScroll);
        }
    }
});

const handleKeyDown = (event) => {
    if (event.key === "Enter" && !event.ctrlKey) {
        event.preventDefault();
        sendMessage();
    } else if (event.key === "Enter" && event.ctrlKey) {
        newMessage.value += "\n";
    }
};

const updateTitle = async () => {
    const messageCount = localMessages.value.length;
    if (messageCount === 2 || messageCount % 6 === 0) {
        try {
            router.post(
                route("conversations.title", props.conversation),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Title updated successfully
                    },
                    onError: (errors) => {
                        console.error("Error updating title:", errors);
                    },
                }
            );
        } catch (err) {
            console.error("Error updating title:", err);
        }
    }
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
    async (newConv, oldConv) => {
        error.value = null;
        // Only update messages if it's a different conversation
        if (!oldConv || newConv.id !== oldConv.id) {
            localMessages.value = [...newConv.messages];
            nextTick(() => {
                scrollToBottom();
                isNearBottom.value = true;
                showScrollButton.value = false;
            });
        }
    },
    { immediate: true, deep: true }
);
</script>

<style lang="scss" scoped></style>
