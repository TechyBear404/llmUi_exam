<template>
    <AuthenticatedLayout>
        <ScrollArea class="w-full">
            <div class="py-6">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div v-if="$page.props.flash.success" class="mb-4">
                        <Alert>
                            <font-awesome-icon
                                icon="fa-solid fa-check"
                                class="mr-2"
                            />
                            {{ $page.props.flash.success }}
                        </Alert>
                    </div>
                    <Card class="border shadow-sm bg-card">
                        <CardHeader>
                            <CardTitle
                                >Modifier l'Instruction Personnalisée</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-8">
                                <div class="space-y-2">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Titre de l'instruction</Label
                                    >
                                    <Input
                                        v-model="form.title"
                                        placeholder="Donnez un titre à cette instruction"
                                        class="w-full bg-background"
                                    />
                                    <div
                                        v-if="form.errors.title"
                                        class="text-sm text-destructive"
                                    >
                                        {{ form.errors.title }}
                                    </div>
                                </div>
                                <Separator />
                                <UserInformationForm :form="form" />
                                <Separator />
                                <AssistantConfigurationForm :form="form" />
                                <Separator />
                                <CustomCommandsForm :form="form" />

                                <div class="flex justify-end space-x-3">
                                    <Button
                                        @click="
                                            router.visit(
                                                route(
                                                    'custom-instructions.index'
                                                )
                                            )
                                        "
                                        variant="outline"
                                        type="button"
                                    >
                                        Annuler
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                    >
                                        <font-awesome-icon
                                            v-if="form.processing"
                                            icon="fa-solid fa-circle-notch"
                                            class="mr-2 animate-spin"
                                        />
                                        {{
                                            form.processing
                                                ? "Enregistrement..."
                                                : "Enregistrer"
                                        }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ScrollArea>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/Components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/Components/ui/card";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Separator } from "@/Components/ui/separator";
import { Alert } from "@/Components/ui/alert";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import UserInformationForm from "@/Components/CustomInstructions/UserInformationForm.vue";
import AssistantConfigurationForm from "@/Components/CustomInstructions/AssistantConfigurationForm.vue";
import CustomCommandsForm from "@/Components/CustomInstructions/CustomCommandsForm.vue";

const props = defineProps({
    instruction: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    title: props.instruction.title,
    user_background: props.instruction.user_background,
    user_interests: props.instruction.user_interests || [],
    knowledge_levels: props.instruction.knowledge_levels || [],
    user_goals: props.instruction.user_goals,
    assistant_background: props.instruction.assistant_background,
    assistant_tone: props.instruction.assistant_tone,
    response_style: props.instruction.response_style,
    response_format: props.instruction.response_format,
    custom_commands: props.instruction.custom_commands || [],
});

const submit = () => {
    form.put(route("custom-instructions.update", props.instruction.id));
};
</script>
