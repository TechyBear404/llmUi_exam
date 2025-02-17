<template>
    <AuthenticatedLayout>
        <ScrollArea class="w-full p-4">
            <div class="py-12">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-semibold text-foreground">
                            Instructions Personnalisées
                        </h2>
                        <Button
                            @click="
                                router.visit(
                                    route('custom-instructions.create')
                                )
                            "
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-plus"
                                class="mr-2"
                            />
                            Créer Nouveau
                        </Button>
                    </div>

                    <Card v-if="instructions.length === 0" class="mt-4">
                        <CardContent
                            class="py-8 text-center text-muted-foreground"
                        >
                            Aucune instruction personnalisée trouvée. Créez
                            votre première !
                        </CardContent>
                    </Card>

                    <div v-else class="grid gap-4 mt-4">
                        <Card
                            v-for="instruction in instructions"
                            :key="instruction.id"
                            class="transition-colors hover:bg-muted/50"
                        >
                            <CardContent class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="space-y-2">
                                        <h3 class="text-xl font-medium">
                                            {{ instruction.title }}
                                        </h3>
                                    </div>
                                    <div class="flex space-x-2">
                                        <Button
                                            @click="
                                                router.visit(
                                                    route(
                                                        'custom-instructions.show',
                                                        instruction.id
                                                    )
                                                )
                                            "
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <font-awesome-icon
                                                icon="fa-solid fa-pen-to-square"
                                            />
                                        </Button>
                                        <Button
                                            @click="
                                                showDeleteConfirm(instruction)
                                            "
                                            variant="destructive"
                                            size="sm"
                                        >
                                            <font-awesome-icon
                                                icon="fa-solid fa-trash"
                                            />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ScrollArea>
    </AuthenticatedLayout>

    <AlertDialog :open="!!instructionToDelete" @update:open="closeDeleteDialog">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Supprimer l'instruction</AlertDialogTitle>
                <AlertDialogDescription>
                    Êtes-vous sûr de vouloir supprimer cette instruction ? Cette
                    action ne peut pas être annulée.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel
                    @click="closeDeleteDialog"
                    :disabled="isDeleting"
                >
                    Annuler
                </AlertDialogCancel>
                <AlertDialogAction
                    @click="confirmDelete"
                    class="bg-red-600 hover:bg-red-700"
                    :disabled="isDeleting"
                >
                    <font-awesome-icon
                        v-if="!isDeleting"
                        icon="fa-solid fa-trash"
                        class="mr-2"
                    />
                    <font-awesome-icon
                        v-else
                        icon="fa-solid fa-circle-notch"
                        class="mr-2 animate-spin"
                    />
                    {{ isDeleting ? "Suppression..." : "Supprimer" }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Alert } from "@/Components/ui/alert";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

const props = defineProps({
    instructions: {
        type: Array,
        required: true,
    },
});

const instructionToDelete = ref(null);
const isDeleting = ref(false);

const showDeleteConfirm = (instruction) => {
    instructionToDelete.value = instruction;
};

const closeDeleteDialog = () => {
    if (!isDeleting.value) {
        instructionToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (instructionToDelete.value && !isDeleting.value) {
        isDeleting.value = true;
        router.delete(
            route("custom-instructions.destroy", instructionToDelete.value.id),
            {
                onSuccess: () => {
                    isDeleting.value = false;
                    closeDeleteDialog();
                },
                onError: () => {
                    isDeleting.value = false;
                },
            }
        );
    }
};
</script>
