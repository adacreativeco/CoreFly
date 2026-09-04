import React from 'react';
import { CrmDeal, DealStage } from '@/types/crm';
import clsx from 'clsx';
import { DollarSign, Building, Calendar, ArrowRight, Trash2 } from 'lucide-react';

interface DealKanbanProps {
  deals: CrmDeal[];
  onStageChange: (dealId: string, newStage: DealStage) => void;
  onDeleteDeal: (dealId: string) => void;
}

const stages: { id: DealStage; title: string; color: string; badgeColor: string }[] = [
  { id: 'lead', title: 'Yeni Fırsat', color: 'bg-slate-50 border-slate-200', badgeColor: 'bg-slate-200 text-slate-800' },
  { id: 'proposal', title: 'Teklif Aşaması', color: 'bg-blue-50 border-blue-200', badgeColor: 'bg-blue-200 text-blue-800' },
  { id: 'negotiation', title: 'Pazarlık / Müzakere', color: 'bg-amber-50 border-amber-200', badgeColor: 'bg-amber-200 text-amber-800' },
  { id: 'won', title: 'Kazanıldı', color: 'bg-emerald-50 border-emerald-200', badgeColor: 'bg-emerald-200 text-emerald-800' },
  { id: 'lost', title: 'Kaybedildi', color: 'bg-rose-50 border-rose-200', badgeColor: 'bg-rose-200 text-rose-800' },
];

export const DealKanban: React.FC<DealKanbanProps> = ({ deals, onStageChange, onDeleteDeal }) => {
  const getDealsByStage = (stage: DealStage) => deals.filter((d) => d.stage === stage);

  const getStageTotal = (stage: DealStage) => {
    return deals
      .filter((d) => d.stage === stage)
      .reduce((sum, d) => sum + Number(d.value || 0), 0);
  };

  const nextStageMap: Record<DealStage, DealStage | null> = {
    lead: 'proposal',
    proposal: 'negotiation',
    negotiation: 'won',
    won: null,
    lost: null,
  };

  return (
    <div className="flex h-full space-x-4 overflow-x-auto pb-4">
      {stages.map((stage) => {
        const stageDeals = getDealsByStage(stage.id);
        const stageTotal = getStageTotal(stage.id);

        return (
          <div
            key={stage.id}
            className={clsx('flex-shrink-0 w-80 rounded-xl border flex flex-col', stage.color)}
          >
            {/* Column Header */}
            <div className="p-4 border-b border-inherit flex items-center justify-between">
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                  {stage.title}
                </h3>
                <span className="text-xs text-gray-500 font-medium">
                  {stageTotal.toLocaleString('tr-TR')} ₺
                </span>
              </div>
              <span className={clsx('px-2 py-0.5 rounded-full text-xs font-bold', stage.badgeColor)}>
                {stageDeals.length}
              </span>
            </div>

            {/* Column Cards */}
            <div className="flex-1 p-3 space-y-3 overflow-y-auto min-h-[350px]">
              {stageDeals.length === 0 ? (
                <div className="h-28 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-xs text-gray-400">
                  Bu aşamada fırsat yok
                </div>
              ) : (
                stageDeals.map((deal) => {
                  const nextStage = nextStageMap[deal.stage];

                  return (
                    <div
                      key={deal.id}
                      className="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow group relative"
                    >
                      <div className="flex justify-between items-start mb-2">
                        <h4 className="font-semibold text-gray-900 dark:text-white text-sm">
                          {deal.title}
                        </h4>
                        <button
                          onClick={() => onDeleteDeal(deal.id)}
                          className="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity p-1"
                          title="Fırsatı Sil"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>

                      {deal.customer_name && (
                        <div className="flex items-center text-xs text-gray-600 dark:text-gray-300 mb-2">
                          <Building className="w-3.5 h-3.5 mr-1 text-gray-400" />
                          <span>{deal.customer_name} {deal.customer_company ? `(${deal.customer_company})` : ''}</span>
                        </div>
                      )}

                      <div className="flex items-center justify-between text-xs pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
                        <div className="flex items-center font-bold text-indigo-600 dark:text-indigo-400">
                          <DollarSign className="w-3.5 h-3.5 mr-0.5" />
                          {Number(deal.value).toLocaleString('tr-TR')} {deal.currency}
                        </div>

                        {deal.expected_close_date && (
                          <div className="flex items-center text-gray-400 text-[11px]">
                            <Calendar className="w-3 h-3 mr-1" />
                            {deal.expected_close_date}
                          </div>
                        )}
                      </div>

                      {/* Quick stage advance */}
                      {nextStage && (
                        <div className="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                          <button
                            onClick={() => onStageChange(deal.id, nextStage)}
                            className="flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded transition-colors"
                          >
                            İlerlet
                            <ArrowRight className="w-3 h-3 ml-1" />
                          </button>
                        </div>
                      )}
                    </div>
                  );
                })
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
};
