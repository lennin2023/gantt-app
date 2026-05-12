import axios from 'axios';
import {
    index,
    store,
    show,
    update,
    destroy,
} from '@/actions/App/Http/Controllers/Api/ProjectController';
import type {
    CreateProjectPayload,
    UpdateProjectPayload,
} from '@/pages/projects/types/project';
import type { Project } from '@/shared/types/models';
import type {
    PaginatedResponse,
    ResourceResponse,
    MessageResponse,
} from '@/types/api';

const projectService = {
    async getProjects(perPage = 10): Promise<PaginatedResponse<Project>> {
        return (
            await axios.get<PaginatedResponse<Project>>(index().url, {
                params: { per_page: perPage },
            })
        ).data;
    },

    async getProject(id: number): Promise<ResourceResponse<Project>> {
        return (await axios.get<ResourceResponse<Project>>(show(id).url)).data;
    },

    async createProject(
        data: CreateProjectPayload,
    ): Promise<ResourceResponse<Project>> {
        return (await axios.post<ResourceResponse<Project>>(store().url, data))
            .data;
    },

    async updateProject(
        id: number,
        data: UpdateProjectPayload,
    ): Promise<ResourceResponse<Project>> {
        return (
            await axios.put<ResourceResponse<Project>>(update(id).url, data)
        ).data;
    },

    async deleteProject(id: number): Promise<MessageResponse> {
        return (await axios.delete<MessageResponse>(destroy(id).url)).data;
    },
};

export default projectService;
