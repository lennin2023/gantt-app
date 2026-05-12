import axios from 'axios';
import { stats } from '@/actions/App/Http/Controllers/Api/DashboardController';
import type { DashboardStats } from '@/pages/dashboard/types/dashboard';

const dashboardService = {
    async getStats(): Promise<DashboardStats> {
        return (await axios.get<DashboardStats>(stats().url)).data;
    },
};

export default dashboardService;
