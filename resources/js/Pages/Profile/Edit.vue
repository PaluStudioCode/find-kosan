<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                Pengaturan Akun
            </h2>
        </template>

        <div class="max-w-3xl mx-auto">
            <Tabs default-value="profile" class="w-full">
                <TabsList class="grid w-full grid-cols-3 mb-6">
                    <TabsTrigger value="profile">Profil</TabsTrigger>
                    <TabsTrigger value="password">Keamanan</TabsTrigger>
                    <TabsTrigger value="danger" class="text-destructive data-[state=active]:text-destructive">Zona Berbahaya</TabsTrigger>
                </TabsList>
                
                <TabsContent value="profile" class="mt-0">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </TabsContent>
                
                <TabsContent value="password" class="mt-0">
                    <UpdatePasswordForm />
                </TabsContent>
                
                <TabsContent value="danger" class="mt-0">
                    <DeleteUserForm />
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
