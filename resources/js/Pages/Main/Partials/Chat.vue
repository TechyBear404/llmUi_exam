<template>
    <div class="flex flex-col h-full">
        <AlertDialog :open="!!error">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Erreur</AlertDialogTitle>
                    <AlertDialogDescription>{{ error }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <Button variant="outline" @click="error = null"
                        >Fermer</Button
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <ScrollArea class="flex-1 min-h-0" ref="scrollArea">
            <div class="px-4">
                <div
                    v-if="localMessages.length === 0"
                    class="flex items-center justify-center h-full min-h-[calc(100vh-16rem)]"
                >
                    <div class="max-w-lg px-4 space-y-6 text-center">
                        <div class="space-y-2">
                            <font-awesome-icon
                                icon="fa-solid fa-comments"
                                class="text-5xl text-primary/80"
                            />
                            <h2 class="text-2xl font-semibold text-foreground">
                                Bienvenue dans votre nouvelle conversation
                            </h2>
                            <p class="text-muted-foreground">
                                Posez votre question ou décrivez ce dont vous
                                avez besoin. L'assistant est là pour vous aider
                                !
                            </p>
                        </div>
                        <div
                            class="p-4 space-y-2 text-sm rounded-lg bg-muted/50"
                        >
                            <p class="font-medium text-foreground">
                                Suggestions :
                            </p>
                            <ul class="space-y-2 text-muted-foreground">
                                <li class="flex items-center gap-2">
                                    <font-awesome-icon
                                        icon="fa-solid fa-lightbulb"
                                        class="text-primary/80"
                                    />
                                    "Peux-tu m'expliquer comment fonctionne..."
                                </li>
                                <li class="flex items-center gap-2">
                                    <font-awesome-icon
                                        icon="fa-solid fa-code"
                                        class="text-primary/80"
                                    />
                                    "Aide-moi à résoudre ce problème de code..."
                                </li>
                                <li class="flex items-center gap-2">
                                    <font-awesome-icon
                                        icon="fa-solid fa-circle-question"
                                        class="text-primary/80"
                                    />
                                    "J'ai besoin d'aide pour..."
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="py-4 m-auto space-y-4"
                    ref="messagesContainer"
                >
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
                                    <span>L'IA réfléchit...</span>
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
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger>
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
                                                                copiedStates.get(
                                                                    message.id
                                                                )
                                                                    ? 'fa-solid fa-clipboard-check'
                                                                    : 'fa-solid fa-clipboard'
                                                            "
                                                            class="w-4 h-4"
                                                        />
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Copier le message</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider
                                            v-if="message.role === 'user'"
                                        >
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <div
                                                        class="transition-colors duration-200 cursor-pointer"
                                                        :class="[
                                                            streaming
                                                                ? 'opacity-50 cursor-not-allowed'
                                                                : '',
                                                        ]"
                                                        @click="
                                                            !streaming &&
                                                                resendMessage(
                                                                    message.content
                                                                )
                                                        "
                                                    >
                                                        <font-awesome-icon
                                                            icon="fa-solid fa-arrow-rotate-right"
                                                            class="w-4 h-4"
                                                            :class="{
                                                                'animate-spin':
                                                                    streaming,
                                                            }"
                                                        />
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>
                                                        {{
                                                            streaming
                                                                ? "En cours d'envoi..."
                                                                : "Renvoyer le message"
                                                        }}
                                                    </p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <div
                                                        class="transition-colors duration-200 cursor-pointer hover:text-destructive"
                                                        @click="
                                                            confirmDeleteMessage(
                                                                message
                                                            )
                                                        "
                                                    >
                                                        <font-awesome-icon
                                                            icon="fa-solid fa-trash"
                                                            class="w-4 h-4"
                                                        />
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Supprimer le message</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
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

        <!-- Dialog de confirmation de suppression -->
        <AlertDialog :open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Confirmer la suppression</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        Êtes-vous sûr de vouloir supprimer ce message ? Cette
                        action est irréversible.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDeleteDialog = false"
                        >Annuler</AlertDialogCancel
                    >
                    <AlertDialogAction
                        @click="deleteMessage"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        <font-awesome-icon
                            v-if="deletingMessage"
                            icon="fa-solid fa-circle-notch"
                            class="w-4 h-4 mr-2 animate-spin"
                        />
                        Supprimer
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

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
                    class="flex-1 bg-muted/50"
                    placeholder="Tapez votre message..."
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
    AlertDialogAction,
    AlertDialogCancel,
} from "@/Components/ui/alert-dialog";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
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
const messageToDelete = ref(null);
const deletingMessage = ref(false);
const showDeleteDialog = ref(false);

const deleteForm = useForm({});

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

    // Only add the user message if it's not a resend (not already in messages)
    const isResend = localMessages.value.some(
        msg => msg.role === 'user' && msg.content === messageContent
    );

    if (!isResend) {
        localMessages.value.push({
            id: `temp-${Date.now()}`,
            role: "user",
            content: messageContent,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
        });
    }

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
        await new Promise((resolve) => {
            setupChannelSubscription(() => resolve());
        });

        await axios.post(route("conversations.ask", props.conversation.id), {
            message: messageContent,
            model: props.model,
        });
    } catch (err) {
        console.error("Error sending message:", err);
        error.value =
            err.response?.data?.message ||
            "Échec de l'envoi du message. Veuillez réessayer.";
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
            // console.log("✅ Successfully subscribed to channel:", channel);
            if (onSubscribed) onSubscribed();
        })
        .error((error) => {
            console.error("❌ Channel subscription error:", error);
            streaming.value = false;
            error.value =
                "Échec de la connexion au chat. Veuillez actualiser la page.";
        })
        .listen(".message.streamed", (event) => {
            // console.log("📨 Message received:", event);

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
                // console.log("⚠️ No assistant message to update");
                return;
            }

            lastMessage.isLoading = false;
            lastMessage.content = event.content;

            if (event.isComplete) {
                // console.log("✅ Message complete");
                streaming.value = false;
                updateTitle();
            }

            if (isNearBottom.value) {
                nextTick(() => scrollToBottom());
            }
        });
};

onMounted(() => {
    setupChannelSubscription();

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
                    onSuccess: () => {},
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

const resendMessage = async (messageContent) => {
    if (streaming.value) return;
    newMessage.value = messageContent;
    await sendMessage();
};

const confirmDeleteMessage = (message) => {
    messageToDelete.value = message;
    showDeleteDialog.value = true;
};

const deleteMessage = () => {
    if (!messageToDelete.value) {
        console.error("No message to delete");
        return;
    }

    deletingMessage.value = true;
    deleteForm.delete(route("messages.destroy", messageToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Supprimer le message localement
            localMessages.value = localMessages.value.filter(
                (m) => m.id !== messageToDelete.value.id
            );
            showDeleteDialog.value = false;
            messageToDelete.value = null;
        },
        onError: (errors) => {
            error.value =
                errors.message || "Échec de la suppression du message";
        },
        onFinish: () => {
            deletingMessage.value = false;
        },
    });
};

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
