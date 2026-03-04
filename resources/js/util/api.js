/**
 * Módulo de API - Arquitetura Moderna e Robusta
 */
export const api = {
  async request(url, config = {}) {
    // 1. Configurações base (Headers padrão)
    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
      ...config.headers
    };

    // 2. Inteligência de Body (JSON por padrão)
    if (config.body && !(config.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      config.body = JSON.stringify(config.body);
    }

    try {
      const response = await fetch(url, { ...config, headers });

      // 3. O GRANDE TRUQUE: Validar se a resposta é OK (Status 200-299)
      if (!response.ok) {
        // Se o status for erro, extraímos a mensagem do backend
        const errorData = await response.json().catch(() => ({}));
        throw { 
            status: response.status, 
            message: errorData.message || 'Erro na requisição',
            data: errorData 
        };
      }

      // 4. Retornar JSON se for JSON, senão texto puro
      const contentType = response.headers.get("content-type");
      return (contentType && contentType.includes("application/json")) 
        ? await response.json() 
        : await response.text();

    } catch (error) {
      console.error(`[API ERROR] ${config.method || 'GET'} ${url}:`, error);
      throw error; // Repassa o erro para o projeto tratar (ex: mostrar no Overlay)
    }
  },

  // Atalhos amigáveis
  get(url, options = {}) {
    return this.request(url, { ...options, method: 'GET' });
  },

  post(url, body = {}, options = {}) {
    return this.request(url, { ...options, method: 'POST', body });
  }
};