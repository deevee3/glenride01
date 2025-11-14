import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const [nodeFile, setNodeFile] = useState<File | null>(null);
    const [edgeFile, setEdgeFile] = useState<File | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [errors, setErrors] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);

    async function handleUpload(
        type: 'nodes' | 'edges',
        file: File | null,
    ) {
        if (! file) {
            setErrors(['Please select a file before uploading.']);
            return;
        }

        setProcessing(true);
        setMessage(null);
        setErrors([]);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const uploadUrl = type === 'nodes' ? '/uploads/nodes' : '/uploads/edges';

            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
                },
                body: formData,
            });

            const payload = await response.json();

            if (! response.ok) {
                setErrors(payload.errors?.map((error: { error: string; row?: number }) => `${error.row ?? 0}: ${error.error}`) ?? ['Upload failed.']);
            } else {
                setMessage(payload.message);
            }
        } catch (error) {
            setErrors(['Unexpected error uploading file.']);
        } finally {
            setProcessing(false);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
                <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Upload Nodes CSV</CardTitle>
                            <CardDescription>Upload your node list with name, type, location, capacity columns.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            <input
                                type="file"
                                accept=".csv,.txt"
                                onChange={(event) => setNodeFile(event.target.files?.[0] ?? null)}
                            />
                            <Button
                                onClick={() => handleUpload('nodes', nodeFile)}
                                disabled={processing}
                            >
                                Upload Nodes
                            </Button>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Upload Edges CSV</CardTitle>
                            <CardDescription>Upload flows with origin, destination, avg_lead_time_days, etc.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3">
                            <input
                                type="file"
                                accept=".csv,.txt"
                                onChange={(event) => setEdgeFile(event.target.files?.[0] ?? null)}
                            />
                            <Button
                                onClick={() => handleUpload('edges', edgeFile)}
                                disabled={processing}
                            >
                                Upload Edges
                            </Button>
                        </CardContent>
                    </Card>
                </div>
                {message && (
                    <div className="mt-6 rounded-md bg-green-100 p-4 text-green-700">
                        {message}
                    </div>
                )}
                {errors.length > 0 && (
                    <div className="mt-6 rounded-md bg-red-100 p-4 text-red-700">
                        <p className="font-semibold">Upload issues:</p>
                        <ul className="list-disc pl-6">
                            {errors.map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
